<?php

declare(strict_types=1);

namespace Sylius\RefundPlugin\Creator;

use Prooph\Common\Messaging\Command;
use Sylius\RefundPlugin\Command\RefundUnits;
use Sylius\RefundPlugin\Model\UnitRefund;
use Sylius\RefundPlugin\Provider\RemainingTotalProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class RefundUnitsCommandCreator implements CommandCreatorInterface
{
    /** @var RemainingTotalProviderInterface */
    private $remainingOrderItemUnitTotalProvider;

    public function __construct(RemainingTotalProviderInterface $remainingOrderItemUnitTotalProvider)
    {
        $this->remainingOrderItemUnitTotalProvider = $remainingOrderItemUnitTotalProvider;
    }

    public function fromRequest(Request $request): Command
    {
        if (!$request->attributes->has('orderNumber')) {
            throw new \InvalidArgumentException('Refunded order number not provided');
        }

        if (
            $request->request->get('sylius_refund_units') === null &&
            $request->request->get('sylius_refund_shipments') === null
        ) {
            throw new \InvalidArgumentException('sylius_refund.at_least_one_unit_should_be_selected_to_refund');
        }

        return new RefundUnits(
            $request->attributes->get('orderNumber'),
            $this->parseIdsToUnitRefunds($request->request->get('sylius_refund_units', [])),
            $this->parseIdsToIntegers($request->request->get('sylius_refund_shipments', [])),
            (int) $request->request->get('sylius_refund_payment_method'),
            $request->request->get('sylius_refund_comment', '')
        );
    }

    /** @return array|UnitRefund[] */
    private function parseIdsToUnitRefunds(array $units): array
    {
        return array_map(function (array $refundUnit): UnitRefund {
            $id = (int) $refundUnit['id'];
            $total = $this->remainingOrderItemUnitTotalProvider->getTotalLeftToRefund($id);

            return new UnitRefund($id, $total);
        }, $units);
    }

    /** @return array|int[] */
    private function parseIdsToIntegers(array $elements): array
    {
        return array_map(function (string $element): int {
            return (int) $element;
        }, $elements);
    }
}