<?php

namespace CSBill\InvoiceBundle\Manager;

use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerAware;

class InvoiceManager extends ContainerAware {

    /**
     * Create an invoice from a quote
     *
     * @param Quote $quote
     */
    public function createFromQuote(Quote $quote)
    {
        $invoice = new Invoice;

        $em = $this->container->get('doctrine')->getManager();

        $metadata = $em->getClassMetadata(get_class($quote));

        $invoiceMetadata = $em->getClassMetadata(get_class($invoice));

        $this->copyFieldValues($quote, $invoice);

        foreach($metadata->getAssociationNames() as $mappingField) {
            if('status' === $mappingField) {
                continue;
            }

            if('items' === $mappingField) {
                $items = $metadata->getFieldValue($quote, $mappingField);

                foreach($items as $item) {
                    $invoiceItem = new Item;
                    $this->copyFieldValues($item, $invoiceItem);
                    $invoice->addItem($invoiceItem);
                }
            } else {
                $invoiceMetadata->setFieldValue($invoice, $mappingField, $metadata->getFieldValue($quote, $mappingField));
            }
        }

        $status = $em->getRepository('CSBillInvoiceBundle:Status')->findOneByName('pending');
        $invoice->setStatus($status);

        return $invoice;
    }

    protected function copyFieldValues($object, $clone)
    {
        $em = $this->container->get('doctrine')->getManager();

        $metadata = $em->getClassMetadata(get_class($object));
        $cloneMetadata = $em->getClassMetadata(get_class($clone));

        foreach($metadata->getFieldNames() as $field) {
            if($cloneMetadata->hasField($field)) {
                if(in_array($field, array('created', 'updated', 'id'), true)) {
                    continue;
                }

                $cloneMetadata->setFieldValue($clone, $field, $metadata->getFieldValue($object, $field));
            }
        }
    }
}