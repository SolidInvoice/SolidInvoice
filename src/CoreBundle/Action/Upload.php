<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Action;

use CSBill\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Upload
{
    use JsonTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, KernelInterface $kernel)
    {
        $this->translator = $translator;
        $this->validator = $validator;
        $this->kernel = $kernel;
    }

    public function __invoke(Request $request)
    {
        $file = $request->files->get('Filedata');

        if (!$file) {
            return $this->json(['status' => 'error', 'message' => $this->translator->trans('invalid_file')]);
        }

        $errors = $this->validator->validate($file, new Image(['maxSize' => '1024k']));

        if (count($errors)) {
            return $this->json(['status' => 'error', 'message' => $errors[0]->getMessage()]);
        }

        $path = dirname($this->kernel->getRootDir()).'/web/uploads';

        $fileName = uniqid().'.'.$file->guessExtension();

        if (!$file->move($path, $fileName)) {
            return $this->json(['status' => 'error', 'message' => $this->translator->trans('upload_error')]);
        }

        return $this->json(['status' => 'success', 'file' => $fileName]);
    }
}
