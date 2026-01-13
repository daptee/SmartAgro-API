<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function generatePaymentSystemPDF()
    {
        $markdownPath = base_path('docs/GUIA_USUARIO_PAGOS.md');

        if (!file_exists($markdownPath)) {
            return response()->json(['error' => 'Archivo de documentación no encontrado'], 404);
        }

        $markdown = file_get_contents($markdownPath);

        // Convertir Markdown a HTML básico
        $html = $this->markdownToHtml($markdown);

        // Generar PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'defaultMediaType' => 'print',
            ]);

        return $pdf->download('Guia_de_Pagos_y_Suscripciones_SmartAgro.pdf');
    }

    private function markdownToHtml($markdown)
    {
        // Estilos CSS para el PDF
        $css = <<<CSS
        <style>
            @page {
                margin: 2cm;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 10pt;
                line-height: 1.6;
                color: #333;
            }
            h1 {
                color: #2C3E50;
                font-size: 24pt;
                margin-top: 20pt;
                margin-bottom: 10pt;
                border-bottom: 3px solid #3498DB;
                padding-bottom: 5pt;
                page-break-after: avoid;
            }
            h2 {
                color: #34495E;
                font-size: 18pt;
                margin-top: 15pt;
                margin-bottom: 8pt;
                border-bottom: 2px solid #95A5A6;
                padding-bottom: 3pt;
                page-break-after: avoid;
            }
            h3 {
                color: #2980B9;
                font-size: 14pt;
                margin-top: 12pt;
                margin-bottom: 6pt;
                page-break-after: avoid;
            }
            h4 {
                color: #16A085;
                font-size: 12pt;
                margin-top: 10pt;
                margin-bottom: 5pt;
                page-break-after: avoid;
            }
            p {
                margin-bottom: 8pt;
                text-align: justify;
            }
            code {
                background-color: #ECF0F1;
                padding: 2pt 4pt;
                border-radius: 3pt;
                font-family: 'Courier New', monospace;
                font-size: 9pt;
                color: #E74C3C;
            }
            pre {
                background-color: #2C3E50;
                color: #ECF0F1;
                padding: 10pt;
                border-radius: 5pt;
                overflow-x: auto;
                font-family: 'Courier New', monospace;
                font-size: 8pt;
                line-height: 1.4;
                margin: 10pt 0;
                page-break-inside: avoid;
            }
            pre code {
                background-color: transparent;
                color: #ECF0F1;
                padding: 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 10pt 0;
                font-size: 9pt;
                page-break-inside: avoid;
            }
            table thead {
                background-color: #3498DB;
                color: white;
            }
            table th {
                padding: 6pt;
                text-align: left;
                border: 1px solid #BDC3C7;
                font-weight: bold;
            }
            table td {
                padding: 6pt;
                border: 1px solid #BDC3C7;
            }
            table tbody tr:nth-child(even) {
                background-color: #ECF0F1;
            }
            ul, ol {
                margin-left: 15pt;
                margin-bottom: 8pt;
            }
            li {
                margin-bottom: 4pt;
            }
            blockquote {
                border-left: 4px solid #3498DB;
                padding-left: 10pt;
                margin: 10pt 0;
                font-style: italic;
                color: #7F8C8D;
            }
            .emoji {
                font-size: 14pt;
            }
            .badge {
                display: inline-block;
                padding: 2pt 6pt;
                border-radius: 3pt;
                font-size: 8pt;
                font-weight: bold;
                margin: 0 2pt;
            }
            .badge-success {
                background-color: #27AE60;
                color: white;
            }
            .badge-warning {
                background-color: #F39C12;
                color: white;
            }
            .badge-danger {
                background-color: #E74C3C;
                color: white;
            }
            .badge-info {
                background-color: #3498DB;
                color: white;
            }
            hr {
                border: none;
                border-top: 2px solid #BDC3C7;
                margin: 15pt 0;
            }
            .page-break {
                page-break-before: always;
            }
            .no-break {
                page-break-inside: avoid;
            }
        </style>
CSS;

        // Convertir Markdown a HTML
        $html = $css . "\n<div class='markdown-body'>\n";

        $lines = explode("\n", $markdown);
        $inCodeBlock = false;
        $inTable = false;
        $tableHeaders = [];
        $codeBlockContent = '';

        foreach ($lines as $line) {
            // Code blocks
            if (preg_match('/^```(.*)$/', $line, $matches)) {
                if (!$inCodeBlock) {
                    $inCodeBlock = true;
                    $codeBlockContent = '';
                    $language = trim($matches[1]);
                } else {
                    $inCodeBlock = false;
                    $html .= "<pre><code>" . htmlspecialchars($codeBlockContent) . "</code></pre>\n";
                }
                continue;
            }

            if ($inCodeBlock) {
                $codeBlockContent .= $line . "\n";
                continue;
            }

            // Headers
            if (preg_match('/^# (.+)$/', $line, $matches)) {
                $html .= "<h1>" . htmlspecialchars($matches[1]) . "</h1>\n";
            } elseif (preg_match('/^## (.+)$/', $line, $matches)) {
                $html .= "<h2>" . htmlspecialchars($matches[1]) . "</h2>\n";
            } elseif (preg_match('/^### (.+)$/', $line, $matches)) {
                $html .= "<h3>" . htmlspecialchars($matches[1]) . "</h3>\n";
            } elseif (preg_match('/^#### (.+)$/', $line, $matches)) {
                $html .= "<h4>" . htmlspecialchars($matches[1]) . "</h4>\n";
            } elseif (preg_match('/^##### (.+)$/', $line, $matches)) {
                $html .= "<h5>" . htmlspecialchars($matches[1]) . "</h5>\n";
            }
            // Horizontal rule
            elseif (preg_match('/^---+$/', $line)) {
                $html .= "<hr>\n";
            }
            // Tables
            elseif (preg_match('/^\|(.+)\|$/', $line)) {
                $cells = array_map('trim', explode('|', trim($line, '|')));

                if (!$inTable) {
                    $inTable = true;
                    $tableHeaders = $cells;
                    $html .= "<table>\n<thead>\n<tr>\n";
                    foreach ($cells as $cell) {
                        $html .= "<th>" . htmlspecialchars($cell) . "</th>\n";
                    }
                    $html .= "</tr>\n</thead>\n<tbody>\n";
                } elseif (preg_match('/^[\|\-: ]+$/', $line)) {
                    // Table separator, skip
                    continue;
                } else {
                    $html .= "<tr>\n";
                    foreach ($cells as $cell) {
                        $html .= "<td>" . htmlspecialchars($cell) . "</td>\n";
                    }
                    $html .= "</tr>\n";
                }
            }
            // End table
            elseif ($inTable && trim($line) === '') {
                $html .= "</tbody>\n</table>\n";
                $inTable = false;
            }
            // Lists
            elseif (preg_match('/^- (.+)$/', $line, $matches)) {
                static $inList = false;
                if (!$inList) {
                    $html .= "<ul>\n";
                    $inList = true;
                }
                $html .= "<li>" . htmlspecialchars($matches[1]) . "</li>\n";
            } elseif (preg_match('/^\d+\. (.+)$/', $line, $matches)) {
                static $inOrderedList = false;
                if (!$inOrderedList) {
                    $html .= "<ol>\n";
                    $inOrderedList = true;
                }
                $html .= "<li>" . htmlspecialchars($matches[1]) . "</li>\n";
            }
            // Close lists
            elseif (trim($line) === '' && (isset($inList) && $inList)) {
                $html .= "</ul>\n";
                $inList = false;
            } elseif (trim($line) === '' && (isset($inOrderedList) && $inOrderedList)) {
                $html .= "</ol>\n";
                $inOrderedList = false;
            }
            // Inline code
            elseif (preg_match('/`([^`]+)`/', $line)) {
                $line = preg_replace('/`([^`]+)`/', '<code>$1</code>', $line);
                $html .= "<p>" . $line . "</p>\n";
            }
            // Bold
            elseif (preg_match('/\*\*(.+?)\*\*/', $line)) {
                $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
                $html .= "<p>" . $line . "</p>\n";
            }
            // Empty line
            elseif (trim($line) === '') {
                $html .= "<br>\n";
            }
            // Regular paragraph
            else {
                $html .= "<p>" . htmlspecialchars($line) . "</p>\n";
            }
        }

        // Close any open table
        if ($inTable) {
            $html .= "</tbody>\n</table>\n";
        }

        $html .= "</div>";

        // Replace emojis with HTML entities or text
        $html = str_replace('✅', '<span class="badge badge-success">SI</span>', $html);
        $html = str_replace('❌', '<span class="badge badge-danger">NO</span>', $html);
        $html = str_replace('⚠️', '<span class="badge badge-warning">ATENCION</span>', $html);
        $html = str_replace('📊', '', $html);
        $html = str_replace('🏗️', '', $html);
        $html = str_replace('💾', '', $html);
        $html = str_replace('🔄', '', $html);
        $html = str_replace('🔔', '', $html);
        $html = str_replace('⏰', '', $html);
        $html = str_replace('🌐', '', $html);
        $html = str_replace('🛡️', '', $html);
        $html = str_replace('🚨', '', $html);
        $html = str_replace('📧', '', $html);
        $html = str_replace('📝', '', $html);
        $html = str_replace('🔍', '', $html);
        $html = str_replace('🚀', '', $html);
        $html = str_replace('🆘', '', $html);
        $html = str_replace('📚', '', $html);
        $html = str_replace('📋', '', $html);
        $html = str_replace('📘', '', $html);
        $html = str_replace('🌾', '', $html);
        $html = str_replace('🆓', '', $html);
        $html = str_replace('🌱', '', $html);
        $html = str_replace('🎯', '', $html);
        $html = str_replace('💳', '', $html);
        $html = str_replace('💰', '', $html);
        $html = str_replace('💵', '', $html);
        $html = str_replace('📅', '', $html);
        $html = str_replace('🎁', '', $html);
        $html = str_replace('🚫', '', $html);
        $html = str_replace('💡', '', $html);
        $html = str_replace('📞', '', $html);
        $html = str_replace('🔵', '', $html);
        $html = str_replace('🟢', '', $html);
        $html = str_replace('🟡', '', $html);
        $html = str_replace('🔴', '', $html);
        $html = str_replace('🔽', '', $html);
        $html = str_replace('🔒', '', $html);
        $html = str_replace('🌟', '', $html);
        $html = str_replace('👤', '', $html);
        $html = str_replace('��', '', $html);
        $html = str_replace('💬', '', $html);
        $html = str_replace('📱', '', $html);
        $html = str_replace('1️⃣', '1)', $html);
        $html = str_replace('2️⃣', '2)', $html);
        $html = str_replace('3️⃣', '3)', $html);
        $html = str_replace('4️⃣', '4)', $html);
        $html = str_replace('⏱️', '', $html);
        $html = str_replace('⏸️', '', $html);

        return $html;
    }
}
