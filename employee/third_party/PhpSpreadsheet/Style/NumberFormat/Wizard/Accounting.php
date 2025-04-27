<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Exception;

class Accounting extends Currency
{
        public function __construct(
        string $currencyCode = '$',
        int $decimals = 2,
        bool $thousandsSeparator = true,
        bool $currencySymbolPosition = self::LEADING_SYMBOL,
        bool $currencySymbolSpacing = self::SYMBOL_WITHOUT_SPACING,
        ?string $locale = null,
        bool $stripLeadingRLM = self::DEFAULT_STRIP_LEADING_RLM
    ) {
        $this->setCurrencyCode($currencyCode);
        $this->setThousandsSeparator($thousandsSeparator);
        $this->setDecimals($decimals);
        $this->setCurrencySymbolPosition($currencySymbolPosition);
        $this->setCurrencySymbolSpacing($currencySymbolSpacing);
        $this->setLocale($locale);
        $this->stripLeadingRLM = $stripLeadingRLM;
    }

        protected function getLocaleFormat(): string
    {
        if (self::icuVersion() < 53.0) {
                        throw new Exception('The Intl extension does not support Accounting Formats without ICU 53');
                    }

                $formatter = new Locale($this->fullLocale, NumberFormatter::CURRENCY_ACCOUNTING);
        $mask = $formatter->format($this->stripLeadingRLM);
        if ($this->decimals === 0) {
            $mask = (string) preg_replace('/\.0+/miu', '', $mask);
        }

        return str_replace('¤', $this->formatCurrencyCode(), $mask);
    }

    public static function icuVersion(): float
    {
        [$major, $minor] = explode('.', INTL_ICU_VERSION);

        return (float) "{$major}.{$minor}";
    }

    private function formatCurrencyCode(): string
    {
        if ($this->locale === null) {
            return $this->currencyCode . '*';
        }

        return "[\${$this->currencyCode}-{$this->locale}]";
    }

    public function format(): string
    {
        if ($this->localeFormat !== null) {
            return $this->localeFormat;
        }

        return sprintf(
            '_-%s%s%s0%s%s%s_-',
            $this->currencySymbolPosition === self::LEADING_SYMBOL ? $this->formatCurrencyCode() : null,
            (
                $this->currencySymbolPosition === self::LEADING_SYMBOL
                && $this->currencySymbolSpacing === self::SYMBOL_WITH_SPACING
            ) ? "\u{a0}" : '',
            $this->thousandsSeparator ? '#,##' : null,
            $this->decimals > 0 ? '.' . str_repeat('0', $this->decimals) : null,
            (
                $this->currencySymbolPosition === self::TRAILING_SYMBOL
                && $this->currencySymbolSpacing === self::SYMBOL_WITH_SPACING
            ) ? "\u{a0}" : '',
            $this->currencySymbolPosition === self::TRAILING_SYMBOL ? $this->formatCurrencyCode() : null
        );
    }
}
