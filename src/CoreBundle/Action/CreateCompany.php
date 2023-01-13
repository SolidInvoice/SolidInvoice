<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Action;

use SolidInvoice\CoreBundle\Form\Handler\CompanyFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;

final class CreateCompany
{
    public function __invoke(FormHandler $formHandler): FormRequest
    {
        return $formHandler->handle(CompanyFormHandler::class);
    }
}
