<?php
namespace HW\Payment\Gateway;

use HW\Traits\Singleton;
use InvalidArgumentException;

final class PaymentGatewayManager
{

    use Singleton;

    /**
     * All Payment gateways
     *
     * @var array<string, PaymentGatewayInterface>
     */
    private array $gateways = [];

    /**
     * Gateways were loaded
     *
     * @var boolean
     */
    private bool $loaded = false;

    /**
     * load all gateways
     *
     * @return void
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $gateways = apply_filters('hw_payment_gateways', []);

        if (!is_array($gateways)) {
            $gateways = [];
        }

        foreach ($gateways as $gateway) {
            
            if (!$gateway instanceof PaymentGatewayInterface) {
                continue; // or throw, if you prefer strictness
            }

            $this->register($gateway);
        }

        // sort by priority (low first)
        //uasort($this->gateways, fn($a, $b) => $a->priority() <=> $b->priority());

        $this->loaded = true;
    }

    /**
     * register a gateway
     *
     * @param PaymentGatewayInterface $gateway
     * @return void
     */
    public function register(PaymentGatewayInterface $gateway): void
    {
        $id = $this->normalizeId($gateway->getId());

        if ($id === '') {

            throw new InvalidArgumentException('Gateway id cannot be empty.');
        }

        if (isset($this->gateways[$id])) {

            throw new InvalidArgumentException(sprintf('Duplicate payment gateway id: "%s".', $id));
        }

        $this->gateways[$id] = $gateway;
    }

    /**
     * Return all gateways
     *
     * @param array $context
     * @return array<string, PaymentGatewayInterface>
     */
    public function all(array $context = []): array
    {
        $this->load();

        if (!$context) {
            return $this->gateways;
        }

        return $this->gateways;

        // filters available gateways
        /* return array_filter(
            $this->gateways,
            fn(PaymentGatewayInterface $g) => $g->isAvailable($context)
        ); */
    }

    /**
     * return payment gateway by id
     *
     * @param string $id
     * @return PaymentGatewayInterface|null
     */
    public function get(string $id): ?PaymentGatewayInterface
    {
        $this->load();
        $id = $this->normalizeId($id);

        return $this->gateways[$id] ?? null;
    }

    /**
     * checks if a payment gateway exists
     *
     * @param string $id
     * @return boolean
     */
    public function exists(string $id): bool
    {
        return (bool) $this->get($id);
    }

    /**
     * normalize id
     *
     * @param string $id
     * @return string
     */
    private function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        // keep it DB-safe and predictable
        return preg_replace('/[^a-z0-9_\-]/', '', $id) ?? '';
    }
}
