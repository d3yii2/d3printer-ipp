<?php

namespace d3yii2\d3printeripp\types;

use obray\ipp\enums\OrientationRequested;
use obray\ipp\enums\PrintQuality;

class PrinterAttributeValues
{
    // Orientation values
    public const ORIENTATION_PORTRAIT = OrientationRequested::PORTRAIT;
    public const ORIENTATION_LANDSCAPE = OrientationRequested::LANDSCAPE;
    public const ORIENTATION_REVERSE_LANDSCAPE = OrientationRequested::REVERSE_LANDSCAPE;
    public const ORIENTATION_REVERSE_PORTRAIT = OrientationRequested::REVERSE_PORTRAIT;

    // Print Quality values
    public const PRINT_QUALITY_NORMAL = PrintQuality::normal;
    public const PRINT_QUALITY_HIGH = PrintQuality::high;
    public const PRINT_QUALITY_DRAFT = PrintQuality::draft;

    // Document Size values
    public const MEDIA_SIZE_A2 = 'iso_a2_420x594mm';
    public const MEDIA_SIZE_A3 = 'iso_a3_297x420mm';
    public const MEDIA_SIZE_A4 = 'iso_a4_210x297mm';
    public const MEDIA_SIZE_A5 = 'iso_a5_148x210mm';


}