<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserNoteController extends Controller
{
    private const COLORS = [
        'neutral',
        'yellow',
        'blue',
        'green',
        'pink',
        'purple',
        'orange',
    ];

    private const HIGHLIGHT_COLORS = [
        'yellow',
        'green',
        'blue',
        'pink',
        'purple',
        'orange',
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = UserNote::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at');

        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($notes) use ($escaped) {
                $notes->where('title', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            });
        }

        return response()->json([
            'data' => $query->limit(500)->get()->map(fn (UserNote $note) => $this->payload($note)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $note = UserNote::create(array_merge($data, [
            'user_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Nota guardada correctamente.',
            'data' => $this->payload($note),
        ], 201);
    }

    public function update(Request $request, int $note)
    {
        $model = $this->ownedNote($request, $note);
        $model->fill($this->validatedData($request));
        $model->save();

        return response()->json([
            'message' => 'Nota actualizada correctamente.',
            'data' => $this->payload($model),
        ]);
    }

    public function destroy(Request $request, int $note)
    {
        $this->ownedNote($request, $note)->delete();

        return response()->json([
            'message' => 'Nota eliminada correctamente.',
        ]);
    }

    private function ownedNote(Request $request, int $id): UserNote
    {
        return UserNote::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160', 'required_without:content'],
            'content' => ['nullable', 'string', 'max:50000', 'required_without:title'],
            'color' => ['required', 'string', Rule::in(self::COLORS)],
            'is_pinned' => ['required', 'boolean'],
            'highlights' => ['nullable', 'array', 'max:500'],
            'highlights.*.start' => ['required', 'integer', 'min:0'],
            'highlights.*.end' => ['required', 'integer', 'min:1'],
            'highlights.*.color' => ['required', 'string', Rule::in(self::HIGHLIGHT_COLORS)],
        ]);

        $title = trim((string) ($validated['title'] ?? ''));
        $content = (string) ($validated['content'] ?? '');
        if ($title === '' && trim($content) === '') {
            throw ValidationException::withMessages([
                'content' => ['Escribe un título o contenido para guardar la nota.'],
            ]);
        }

        $validated['title'] = $title === '' ? null : $title;
        $validated['content'] = $content === '' ? null : $content;
        $validated['highlights'] = $this->sanitizeHighlights(
            $validated['highlights'] ?? [],
            $this->utf16Length($content)
        );

        return $validated;
    }

    private function sanitizeHighlights(array $highlights, int $contentLength): array
    {
        $clean = [];
        foreach ($highlights as $highlight) {
            $start = min(max((int) $highlight['start'], 0), $contentLength);
            $end = min(max((int) $highlight['end'], 0), $contentLength);
            if ($end <= $start) {
                continue;
            }

            $clean[] = [
                'start' => $start,
                'end' => $end,
                'color' => (string) $highlight['color'],
            ];
        }

        usort($clean, fn (array $a, array $b) => $a['start'] <=> $b['start']);
        return $clean;
    }

    private function utf16Length(string $value): int
    {
        // Flutter expresa las selecciones en unidades UTF-16. Conservar la
        // misma métrica evita desplazar resaltados cuando hay emoji.
        return (int) (strlen(mb_convert_encoding($value, 'UTF-16LE', 'UTF-8')) / 2);
    }

    private function payload(UserNote $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'color' => $note->color,
            'highlights' => $note->highlights ?: [],
            'is_pinned' => (bool) $note->is_pinned,
            'created_at' => optional($note->created_at)->toISOString(),
            'updated_at' => optional($note->updated_at)->toISOString(),
        ];
    }
}
