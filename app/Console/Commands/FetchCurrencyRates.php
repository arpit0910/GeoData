<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Country;
use App\Models\CurrencyConversion;
use Illuminate\Support\Facades\Http;
use Exception;

class FetchCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:fetch-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the latest currency conversion rates against INR and USD';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Fetching latest currency rates...');

        try {
            // Fetch USD rates
            $usdResponse = Http::get('https://api.frankfurter.dev/v2/rates?base=USD');
            if ($usdResponse->failed()) {
                throw new Exception('Failed to fetch rates from USD base API.');
            }
            $usdData = $usdResponse->json();
            $usdRates = [];
            foreach ($usdData as $record) {
                if (isset($record['quote']) && isset($record['rate'])) {
                    $usdRates[$record['quote']] = $record['rate'];
                }
            }

            // Fetch INR rates
            $inrResponse = Http::get('https://api.frankfurter.dev/v2/rates?base=INR');
            if ($inrResponse->failed()) {
                throw new Exception('Failed to fetch rates from INR base API.');
            }
            $inrData = $inrResponse->json();
            $inrRates = [];
            foreach ($inrData as $record) {
                if (isset($record['quote']) && isset($record['rate'])) {
                    $inrRates[$record['quote']] = $record['rate'];
                }
            }

            $countries = Country::whereNotNull('currency')->get();

            foreach ($countries as $country) {
                $currencyCode = strtoupper($country->currency);
                
                // Find rates. Note: If the currency is USD it won't be in $usdRates, 
                // we should handle that (USD rate against USD is 1)
                $usdRateValue = null;
                if ($currencyCode === 'USD') {
                    $usdRateValue = 1;
                } elseif (isset($usdRates[$currencyCode])) {
                    $usdRateValue = $usdRates[$currencyCode];
                }

                $inrRateValue = null;
                if ($currencyCode === 'INR') {
                    $inrRateValue = 1;
                } elseif (isset($inrRates[$currencyCode])) {
                    $inrRateValue = $inrRates[$currencyCode];
                }

                if ($usdRateValue !== null || $inrRateValue !== null) {
                    CurrencyConversion::updateOrCreate(
                        ['country_id' => $country->id, 'currency' => $currencyCode],
                        [
                            'usd_conversion_rate' => $usdRateValue,
                            'inr_conversion_rate' => $inrRateValue,
                        ]
                    );
                }
            }

            $this->info('Currency rates updated successfully!');
            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
