<?php

namespace Modules\Fitness\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

class FitnessQrCodeService
{
    public function dataUri(string $data, int $size = 160): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
            foregroundColor: new Color(17, 24, 39),
            backgroundColor: new Color(255, 255, 255),
        );

        return $builder->build()->getDataUri();
    }
}
