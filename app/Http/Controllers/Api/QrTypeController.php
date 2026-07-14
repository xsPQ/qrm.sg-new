<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\AnonymousCreator;
use Illuminate\Http\JsonResponse;

class QrTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $creator = app(AnonymousCreator::class);
        $types = [];

        foreach ($creator->typeOptions() as $type) {
            $fields = [];

            foreach ($creator->fields($type['value']) as $field) {
                $definition = [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'type' => $field['input'],
                    'required' => $field['required'],
                ];

                if (array_key_exists('maxlength', $field)) {
                    $definition['maxlength'] = $field['maxlength'];
                }

                if (array_key_exists('placeholder', $field)) {
                    $definition['placeholder'] = $field['placeholder'];
                }

                if (array_key_exists('options', $field)) {
                    $definition['options'] = $field['options'];
                }

                $fields[] = $definition;
            }

            $types[] = [
                'id' => $type['value'],
                'label' => $type['label'],
                'description' => $type['description'],
                'icon' => $type['icon'],
                'fields' => $fields,
            ];
        }

        return response()->json(['types' => $types]);
    }
}
