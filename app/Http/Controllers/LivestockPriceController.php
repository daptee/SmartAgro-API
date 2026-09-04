<?php

namespace App\Http\Controllers;

use App\Models\LivestockPrice;
use App\Models\Product;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;

class LivestockPriceController extends Controller
{
    private const MAG_URL = 'https://www.mercadoagroganadero.com.ar/dll/hacienda1.dll/haciinfo000002';
    private const MAG_SOURCE = 'mercadoagroganadero';

    // Categoría (primera palabra del renglón) => producto del catálogo, tomando el "Promedio" del subtotal del grupo
    private const MAG_PRODUCTS = [
        'NOVILLOS' => 'Novillo (promedio)',
        'VAQUILLONAS' => 'Vaquillona (promedio)',
    ];

    private const ESC_BASE_URL = 'https://www.entresurcosycorralesya.com/';
    private const ESC_SOURCE = 'entresurcosycorralesya';

    // Un módulo por página de precios del sitio. Los índices son la posición real de la celda <td>, contando la Categoría como 0.
    private const ESC_MODULES = [
        'ternera' => [
            'endpoint' => 'ajax-modulo-ternera.php',
            'quantity_index' => 1, // Cantidad
            'value_index' => 2, // Prom. Kilo
            'products' => [
                'Terneras 130-150 Kg.' => 'Ternera 130-150 Kg',
                'Terneras 170-190 Kg.' => 'Terneras 170-190 Kg',
                'Vaquillonas 210-250 Kg.' => 'Terneras 210-250 Kg',
            ],
        ],
        'ternero' => [
            'endpoint' => 'ajax-modulo-ternero.php',
            'quantity_index' => 1, // Cantidad
            'value_index' => 2, // Prom. Kilo
            'products' => [
                'Terneros 130-160 Kg.' => 'Ternero 130-160 Kg',
                'Terneros 180-200 Kg.' => 'Terneros 180-200 Kg',
                'Terneros 230-260 Kg.' => 'Terneros 230-260 Kg',
            ],
        ],
        'vientre' => [
            'endpoint' => 'ajax-modulo-vientre.php',
            'quantity_index' => 1, // Cantidad
            'value_index' => 2, // Prom. Bulto (no tiene precio por kilo)
            'products' => [
                'Vacas C. Gtia. Preñez Nueva' => 'Vaca Gtía. Preñez Nueva',
            ],
        ],
        'toros' => [
            'endpoint' => 'ajax-modulo-precio-toros.php',
            'quantity_index' => 2, // Vendidos (no "A venta", que sólo indica que fue ofrecido)
            'value_index' => 3, // Promedio
            'products' => [
                'A.ANGUS PC. NEGRO' => 'A.ANGUS PC. NEGRO',
                'BRANGUS C.' => 'BRANGUS C.',
                'BRAFORD C.' => 'BRAFORD C.',
                'LIMANGUS PC.' => 'LIMANGUS PC.',
            ],
        ],
    ];

    // GET - Scrapea Mercado Agroganadero y Entre Surcos y Corrales Ya, y actualiza el mes en curso (público, sin token)
    public function refresh(Request $request)
    {
        $message = "Error al actualizar las cotizaciones de ganadería";
        $action = "Actualizar cotizaciones de ganadería (público)";
        $data = null;

        try {
            $hasta = Carbon::now();
            $desde = Carbon::now()->subDays(6); // últimos 7 días

            $prices = array_merge(
                $this->fetchMagPrices($desde, $hasta),
                $this->fetchEscPrices($desde, $hasta)
            );

            if (empty($prices)) {
                throw new Exception("No se pudieron extraer cotizaciones de ganadería de ninguna fuente (posible bloqueo o cambio de estructura del HTML)");
            }

            $periodo = Carbon::now()->format('Y-m');
            $data = [];

            foreach ($prices as $entry) {
                $data[] = LivestockPrice::updateOrCreate(
                    ['product_id' => $entry['product_id'], 'periodo' => $periodo],
                    ['price' => $entry['price'], 'source' => $entry['source']]
                )->load('product');
            }

            Log::info('Refresh de cotizaciones de ganadería', [
                'periodo' => $periodo,
                'cantidad' => count($data),
                'cotizaciones' => array_map(fn ($lp) => [
                    'product_id' => $lp->product_id,
                    'producto' => $lp->product->name,
                    'price' => $lp->price,
                    'source' => $lp->source,
                ], $data),
            ]);

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // GET LATEST - Retorna, por cada producto, su cotización más reciente (público, sin token)
    public function latest(Request $request)
    {
        $message = "Error al obtener las últimas cotizaciones de ganadería";
        $action = "Últimas cotizaciones de ganadería (público)";
        $data = null;

        try {
            $data = LivestockPrice::with('product')
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('livestock_prices')
                        ->groupBy('product_id');
                })
                ->get();

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    /**
     * @return array<int, array{product_id: int, price: float, source: string}>
     */
    private function fetchMagPrices(Carbon $desde, Carbon $hasta): array
    {
        $response = Http::timeout(30)->get(self::MAG_URL, [
            'txtFECHAINI' => $desde->format('d/m/Y'),
            'txtFECHAFIN' => $hasta->format('d/m/Y'),
            'CP' => '',
            'LISTADO' => 'SI',
        ]);

        if (!$response->successful()) {
            throw new Exception("No se pudo acceder a Mercado Agroganadero (HTTP {$response->status()})");
        }

        $promedios = $this->parseMagPromedios($response->body());

        $results = [];
        foreach (self::MAG_PRODUCTS as $group => $productName) {
            if (!isset($promedios[$group])) {
                continue;
            }

            $product = Product::where('name', $productName)->first();
            if (!$product) {
                continue;
            }

            $results[] = [
                'product_id' => $product->id,
                'price' => $promedios[$group],
                'source' => self::MAG_SOURCE,
            ];
        }

        return $results;
    }

    // Devuelve, por cada grupo de categorías (ej. "NOVILLOS"), el valor "Promedio" de su fila de subtotal
    private function parseMagPromedios(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $rows = $xpath->query("//table[contains(@class, 'table-striped')]//tr");

        $promedios = [];
        $currentGroup = null;

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 6) {
                continue;
            }

            $label = trim($cells->item(0)->textContent);

            if ($label !== '') {
                $currentGroup = strtok($label, " \t");
                continue;
            }

            if ($currentGroup === null || isset($promedios[$currentGroup])) {
                continue;
            }

            $promedio = $this->parseArgentineNumber(trim($cells->item(3)->textContent));
            $cabezas = $this->parseArgentineNumber(trim($cells->item(5)->textContent));

            if ($promedio !== null && $cabezas !== null && $cabezas > 0) {
                $promedios[$currentGroup] = $promedio;
            }
        }

        return $promedios;
    }

    /**
     * @return array<int, array{product_id: int, price: float, source: string}>
     */
    private function fetchEscPrices(Carbon $desde, Carbon $hasta): array
    {
        $results = [];

        foreach (self::ESC_MODULES as $module) {
            $response = Http::timeout(30)->get(self::ESC_BASE_URL . $module['endpoint'], [
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d'),
            ]);

            if (!$response->successful()) {
                throw new Exception("No se pudo acceder a Entre Surcos y Corrales Ya - {$module['endpoint']} (HTTP {$response->status()})");
            }

            $rows = $this->parseEscTable($response->body(), $module['quantity_index'], $module['value_index']);

            foreach ($module['products'] as $sourceLabel => $productName) {
                if (!isset($rows[$sourceLabel])) {
                    continue;
                }

                $product = Product::where('name', $productName)->first();
                if (!$product) {
                    continue;
                }

                $results[] = [
                    'product_id' => $product->id,
                    'price' => $rows[$sourceLabel]['value'],
                    'source' => self::ESC_SOURCE,
                ];
            }
        }

        return $results;
    }

    // Devuelve, por cada categoría de la tabla, ['cantidad' => .., 'value' => ..] tomando las celdas $quantityIndex/$valueIndex (posición real de <td>, Categoría = 0)
    private function parseEscTable(string $html, int $quantityIndex, int $valueIndex): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $rows = $xpath->query("//table//tbody/tr");

        $data = [];
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length <= max($quantityIndex, $valueIndex)) {
                continue;
            }

            $label = trim($cells->item(0)->textContent);
            if ($label === '') {
                continue;
            }

            $cantidad = $this->parseArgentineNumber(trim($cells->item($quantityIndex)->textContent));
            $value = $this->parseArgentineNumber(trim($cells->item($valueIndex)->textContent));

            if ($cantidad !== null && $cantidad > 0 && $value !== null) {
                $data[$label] = ['cantidad' => $cantidad, 'value' => $value];
            }
        }

        return $data;
    }

    // Convierte números en formato argentino ("4.202,636" o "51.375.000") a float. Devuelve null si no es numérico (ej. "-------")
    private function parseArgentineNumber(string $raw): ?float
    {
        if ($raw === '' || !preg_match('/^-?[\d.,]+$/', $raw)) {
            return null;
        }

        $normalized = str_replace(',', '.', str_replace('.', '', $raw));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
