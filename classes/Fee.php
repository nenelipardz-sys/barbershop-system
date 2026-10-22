<?php
/**
 * Fee — polymorphic fee calculation.
 * Every rate exposes the same calculate() method; the result differs per subclass.
 */
abstract class Fee
{
    abstract public function calculate(float $price): float;

    abstract public function label(): string;

    abstract public function type(): string;

    public function discount(float $price): float
    {
        return round($price - $this->calculate($price), 2);
    }

    /** Factory: returns the right fee object for a discount type. */
    public static function for(string $type): Fee
    {
        switch ($type) {
            case 'senior':
                return new SeniorDiscount();
            case 'pwd':
                return new PwdDiscount();
            default:
                return new StandardRate();
        }
    }

    public static function types(): array
    {
        return ['none' => 'Standard Rate', 'senior' => 'Senior Citizen (20% off)', 'pwd' => 'PWD (20% off)'];
    }
}

class StandardRate extends Fee
{
    public function calculate(float $price): float
    {
        return round($price, 2);
    }

    public function label(): string
    {
        return 'Standard Rate';
    }

    public function type(): string
    {
        return 'none';
    }
}

class SeniorDiscount extends Fee
{
    public function calculate(float $price): float
    {
        return round($price * (1 - DISCOUNT_RATE), 2);
    }

    public function label(): string
    {
        return 'Senior Citizen Discount';
    }

    public function type(): string
    {
        return 'senior';
    }
}

class PwdDiscount extends Fee
{
    public function calculate(float $price): float
    {
        return round($price * (1 - DISCOUNT_RATE), 2);
    }

    public function label(): string
    {
        return 'PWD Discount';
    }

    public function type(): string
    {
        return 'pwd';
    }
}
