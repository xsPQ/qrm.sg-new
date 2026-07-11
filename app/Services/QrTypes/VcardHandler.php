<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

class VcardHandler implements QrTypeHandler
{
    public function handle(QrCode $qrCode, Request $request): mixed
    {
        $firstName = $qrCode->content['first_name'] ?? '';
        $lastName = $qrCode->content['last_name'] ?? '';
        $organization = $qrCode->content['organization'] ?? null;
        $title = $qrCode->content['title'] ?? null;
        $email = $qrCode->content['email'] ?? null;
        $phoneMobile = $qrCode->content['phone_mobile'] ?? null;
        $phoneWork = $qrCode->content['phone_work'] ?? null;
        $website = $qrCode->content['website'] ?? null;
        $addressStreet = $qrCode->content['address_street'] ?? null;
        $addressCity = $qrCode->content['address_city'] ?? null;
        $addressZip = $qrCode->content['address_zip'] ?? null;
        $addressCountry = $qrCode->content['address_country'] ?? null;

        $fullName = trim($firstName . ' ' . $lastName);

        $vcardUrl = $this->generateVcardUrl(
            $firstName,
            $lastName,
            $organization,
            $title,
            $email,
            $phoneMobile,
            $phoneWork,
            $website,
            $addressStreet,
            $addressCity,
            $addressZip,
            $addressCountry,
        );

        return view('qr-types.vcard', [
            'qrCode' => $qrCode,
            'name' => $fullName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'organization' => $organization,
            'title' => $title,
            'email' => $email,
            'phoneMobile' => $phoneMobile,
            'phoneWork' => $phoneWork,
            'website' => $website,
            'addressStreet' => $addressStreet,
            'addressCity' => $addressCity,
            'addressZip' => $addressZip,
            'addressCountry' => $addressCountry,
            'vcardUrl' => $vcardUrl,
        ]);
    }

    private function generateVcardUrl(
        string $firstName,
        string $lastName,
        ?string $organization,
        ?string $title,
        ?string $email,
        ?string $phoneMobile,
        ?string $phoneWork,
        ?string $website,
        ?string $addressStreet,
        ?string $addressCity,
        ?string $addressZip,
        ?string $addressCountry,
    ): string {
        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "FN:" . $this->escapeVcardText(trim($firstName . ' ' . $lastName)) . "\r\n";
        $vcard .= "N:" . $this->escapeVcardText($lastName) . ";" . $this->escapeVcardText($firstName) . ";;;\r\n";

        if ($organization) {
            $vcard .= "ORG:" . $this->escapeVcardText($organization) . "\r\n";
        }

        if ($title) {
            $vcard .= "TITLE:" . $this->escapeVcardText($title) . "\r\n";
        }

        if ($email) {
            $vcard .= "EMAIL;TYPE=INTERNET:" . $this->escapeVcardText($email) . "\r\n";
        }

        if ($phoneMobile) {
            $vcard .= "TEL;TYPE=CELL:" . $this->escapeVcardText($phoneMobile) . "\r\n";
        }

        if ($phoneWork) {
            $vcard .= "TEL;TYPE=WORK:" . $this->escapeVcardText($phoneWork) . "\r\n";
        }

        if ($website) {
            $vcard .= "URL:" . $this->escapeVcardText($website) . "\r\n";
        }

        if ($addressStreet || $addressCity || $addressZip || $addressCountry) {
            $vcard .= "ADR;TYPE=WORK:;;"
                . $this->escapeVcardText($addressStreet ?? '') . ";"
                . $this->escapeVcardText($addressCity ?? '') . ";"
                . ";;"
                . $this->escapeVcardText($addressZip ?? '') . ";"
                . $this->escapeVcardText($addressCountry ?? '')
                . "\r\n";
        }

        $vcard .= "END:VCARD\r\n";

        return 'data:text/vcard;charset=utf-8,' . rawurlencode($vcard);
    }

    private function escapeVcardText(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', ''],
            $text,
        );
    }
}
