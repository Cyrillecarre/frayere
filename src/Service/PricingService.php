<?php

namespace App\Service;

class PricingService
{
    private $pricesNumberOfFishers = [
        1 => [
            2 => 50,
            3 => 70,
            4 => 85,
            5 => 100,
            6 => 115,
            7 => 140,
        ],
        2 => [
            2 => 80,
            3 => 120,
            4 => 150,
            5 => 180,
            6 => 210,
            7 => 240,
        ],
    ];

    private $pricesPellets = [
        0 => 0,
        1 => 40,
        2 => 80,
        3 => 110,
        4 => 135,
        5 => 160,
    ];

    private $pricesGraines = [
        0 => 0,
        1 => 30,
        2 => 60,
        3 => 90,
        4 => 120,
        5 => 150,
    ];

    public function calculatePrice(int $numNights, int $numFishers, array $additionalProducts): int
    {
        if (!isset($this->pricesNumberOfFishers[$numFishers][$numNights])) {
            throw new \InvalidArgumentException('Price not defined for the given parameters.');
        }

        $totalPrice = $this->pricesNumberOfFishers[$numFishers][$numNights];

        foreach ($additionalProducts as $product => $quantity) {
            switch ($product) {
                case 'pellets':
                    if (isset($this->pricesPellets[$quantity])) {
                        $totalPrice += $this->pricesPellets[$quantity];
                    } else {
                        throw new \InvalidArgumentException('Price not defined for the given product and quantity.');
                    }
                    break;
                case 'graines':
                    if (isset($this->pricesGraines[$quantity])) {
                        $totalPrice += $this->pricesGraines[$quantity];
                    } else {
                        throw new \InvalidArgumentException('Price not defined for the given product and quantity.');
                    }
                    break;
                default:
                    throw new \InvalidArgumentException('Product not recognized.');
            }
        }

        return $totalPrice;
    }
}


    