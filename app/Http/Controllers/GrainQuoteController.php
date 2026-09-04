<?php

namespace App\Http\Controllers;

use App\Models\GrainQuote;
use App\Models\Audith;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

class GrainQuoteController extends Controller
{
    private const SOURCE_URL = 'https://www.bolsadecereales.com/';

    private const TABLES = [
        'trigo' => 'flash-trigo',
        'maiz' => 'flash-maiz',
        'soja' => 'flash-soja',
        'girasol' => 'flash-girasol',
    ];

    // GET - Scrapea el Flash de Cotizaciones de la Bolsa de Cereales y actualiza el mes en curso (público, sin token)
    public function refresh(Request $request)
    {
        $message = "Error al actualizar las cotizaciones de granos";
        $action = "Actualizar cotizaciones de granos (público)";
        $data = null;

        try {
            $html = $this->fetchSourceHtml();
            $granos = $this->parseFlashCotizaciones($html);

            if ($this->countQuotes($granos) === 0) {
                throw new Exception("No se pudieron extraer cotizaciones de la página de origen (posible bloqueo del sitio o cambio de estructura del HTML)");
            }

            // Solo se actualiza el registro del mes en curso; los meses anteriores quedan como historial
            $periodo = Carbon::now()->format('Y-m');

            $data = GrainQuote::updateOrCreate(
                ['periodo' => $periodo],
                ['data' => $granos]
            );
            // updateOrCreate no toca updated_at si el JSON no cambió respecto a la última corrida;
            // se fuerza para que updated_at siempre refleje la última vez que el refresh trajo este dato.
            $data->touch();

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // GET LATEST - Retorna el período más actual guardado (público, sin token)
    public function latest(Request $request)
    {
        $message = "Error al obtener las últimas cotizaciones de granos";
        $action = "Últimas cotizaciones de granos (público)";
        $data = null;

        try {
            $data = GrainQuote::orderBy('periodo', 'desc')->first();

            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Audith::new(Auth::user()->id ?? null, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage()], 500);
        }

        return response(compact("data"));
    }

    // El sitio está protegido por un challenge anti-bot de Cloudflare que bloquea las peticiones
    // hechas directamente desde IPs de datacenter, así que la descarga se delega a ScraperAPI
    // (resuelve el challenge y devuelve el HTML ya renderizado).
    private function fetchSourceHtml(): string
    {
        $apiKey = config('services.scraperapi.key');

        if (empty($apiKey)) {
            throw new Exception("Falta configurar SCRAPERAPI_KEY en el entorno");
        }

        $response = Http::timeout(30)->get('https://api.scraperapi.com/', [
            'api_key' => $apiKey,
            'url' => self::SOURCE_URL,
        ]);

        if (!$response->successful()) {
            throw new Exception("No se pudo acceder a la Bolsa de Cereales vía ScraperAPI (HTTP {$response->status()})");
        }

        $html = $response->body();

        if (trim($html) === '') {
            throw new Exception("La Bolsa de Cereales devolvió una respuesta vacía");
        }

        return $html;
    }

    private function countQuotes(array $granos): int
    {
        $total = 0;

        foreach ($granos['granos'] ?? [] as $sections) {
            foreach ($sections as $rows) {
                $total += count($rows);
            }
        }

        return $total;
    }

    private function parseFlashCotizaciones(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $fechaNodes = $xpath->query("//section[contains(@class, 'flash-cotizaciones')]//div[contains(@class, 'fecha-modulo-sidebar')]");
        $fecha = $fechaNodes->length ? trim($fechaNodes->item(0)->textContent) : null;

        $granos = [];
        foreach (self::TABLES as $grano => $tableId) {
            $granos[$grano] = $this->parseFlashTable($xpath, $tableId);
        }

        return [
            'fecha' => $fecha,
            'granos' => $granos,
        ];
    }

    private function parseFlashTable(DOMXPath $xpath, string $tableId): array
    {
        $sections = [];
        $currentSection = null;

        $rows = $xpath->query("//table[@id='{$tableId}']/tbody/tr");

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            // Fila de encabezado de sección (colspan=4, ej. "Disponibles", "FOB MAGyP")
            if ($cells->length === 1) {
                $currentSection = trim($cells->item(0)->textContent);
                $sections[$currentSection] = [];
                continue;
            }

            if ($cells->length >= 4 && $currentSection !== null) {
                $mercado = trim($cells->item(0)->textContent);
                $posicion = trim($cells->item(1)->textContent);
                $variacion = $this->parseVariacion($cells->item(2));
                $precio = trim($cells->item(3)->textContent);

                $sections[$currentSection][] = [
                    'mercado' => $mercado,
                    'posicion' => $posicion !== '' ? $posicion : null,
                    'variacion' => $variacion,
                    'precio' => $precio !== '' ? $precio : null,
                ];
            }
        }

        return $sections;
    }

    private function parseVariacion(DOMElement $cell): ?string
    {
        $spans = $cell->getElementsByTagName('span');

        if ($spans->length === 0) {
            return null;
        }

        $class = $spans->item(0)->getAttribute('class');

        if (str_contains($class, 'up')) {
            return 'up';
        }

        if (str_contains($class, 'down')) {
            return 'down';
        }

        // Sin clase up/down: puede ser "sin variación" (icono-igual.svg) o "sin dato" (ic-linea.png)
        $images = $cell->getElementsByTagName('img');
        $icon = $images->length ? $images->item(0)->getAttribute('src') : '';

        return str_contains($icon, 'icono-igual') ? 'equal' : null;
    }
}
