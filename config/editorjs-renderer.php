<?php
/**
 * Helper: Render Editor.js JSON to HTML
 * Converts Editor.js blocks to HTML markup
 */

function renderEditorJS($json)
{
    if (empty($json)) {
        return '';
    }

    $data = json_decode($json, true);
    if (!$data || !isset($data['blocks'])) {
        return '';
    }

    $html = '';

    foreach ($data['blocks'] as $block) {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        switch ($type) {
            case 'header':
                $level = $data['level'] ?? 2;
                $text = htmlspecialchars($data['text'] ?? '');
                $html .= "<h{$level} class='editor-header mb-4 font-bold text-white'>{$text}</h{$level}>";
                break;

            case 'paragraph':
                $text = $data['text'] ?? '';
                $html .= "<p class='editor-paragraph mb-4 text-gray-300 leading-relaxed'>{$text}</p>";
                break;

            case 'list':
                $style = $data['style'] ?? 'unordered';
                $items = $data['items'] ?? [];
                $tag = $style === 'ordered' ? 'ol' : 'ul';
                $class = $style === 'ordered' ? 'list-decimal' : 'list-disc';
                $html .= "<{$tag} class='editor-list {$class} ml-6 mb-4 text-gray-300'>";
                foreach ($items as $item) {
                    $html .= "<li class='mb-2'>{$item}</li>";
                }
                $html .= "</{$tag}>";
                break;

            case 'image':
                $url = htmlspecialchars($data['file']['url'] ?? '');
                $caption = htmlspecialchars($data['caption'] ?? '');
                $html .= "<figure class='editor-image mb-6'>";
                $html .= "<img src='{$url}' alt='{$caption}' class='w-full rounded-lg shadow-lg'>";
                if ($caption) {
                    $html .= "<figcaption class='text-center text-sm text-gray-500 mt-2 italic'>{$caption}</figcaption>";
                }
                $html .= "</figure>";
                break;

            case 'quote':
                $text = $data['text'] ?? '';
                $caption = $data['caption'] ?? '';
                $html .= "<blockquote class='editor-quote border-l-4 border-blue-500 pl-4 py-2 mb-6 bg-slate-800 rounded-r-lg'>";
                $html .= "<p class='text-lg text-gray-200 italic mb-2'>{$text}</p>";
                if ($caption) {
                    $html .= "<cite class='text-sm text-gray-400 not-italic'>— {$caption}</cite>";
                }
                $html .= "</blockquote>";
                break;

            case 'code':
                $code = htmlspecialchars($data['code'] ?? '');
                $html .= "<pre class='editor-code bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto mb-6'><code>{$code}</code></pre>";
                break;

            case 'delimiter':
                $html .= "<hr class='editor-delimiter my-8 border-slate-700'>";
                break;

            case 'table':
                $content = $data['content'] ?? [];
                $html .= "<div class='editor-table overflow-x-auto mb-6'>";
                $html .= "<table class='min-w-full border border-slate-700'>";
                foreach ($content as $rowIndex => $row) {
                    $html .= "<tr>";
                    foreach ($row as $cell) {
                        $tag = $rowIndex === 0 ? 'th' : 'td';
                        $class = $tag === 'th' ? 'bg-slate-800 font-bold' : 'bg-slate-900';
                        $html .= "<{$tag} class='{$class} border border-slate-700 px-4 py-2 text-gray-300'>{$cell}</{$tag}>";
                    }
                    $html .= "</tr>";
                }
                $html .= "</table></div>";
                break;

            case 'warning':
                $title = htmlspecialchars($data['title'] ?? 'Warning');
                $message = $data['message'] ?? '';
                $html .= "<div class='editor-warning bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-6 rounded-r-lg'>";
                $html .= "<p class='font-bold text-yellow-400 mb-2'>{$title}</p>";
                $html .= "<p class='text-gray-300'>{$message}</p>";
                $html .= "</div>";
                break;

            default:
                // Unknown block type - skip or render as paragraph
                if (isset($data['text'])) {
                    $html .= "<p class='mb-4 text-gray-300'>{$data['text']}</p>";
                }
        }
    }

    return $html;
}
