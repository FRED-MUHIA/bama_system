<?php

namespace Modules\Hospitality\Controllers\Concerns;

trait NormalizesHospitalityInput
{
    private function zeroBlankNumbers(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $data[$field] = 0;
            }
        }

        return $data;
    }
}
