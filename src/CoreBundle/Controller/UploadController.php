<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Image;

class UploadController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function imageUploadAction(Request $request)
    {
        $file = $request->files->get('Filedata');

        if ($file === null) {
            $data = ['status' => 'error', 'message' => $this->trans('invalid_file')];
        } else {
            $errors = $this->get('validator')->validateValue($file, new Image(['maxSize' => '1024k']));

            if (count($errors) > 0) {
                $data = ['status' => 'error', 'message' => $errors[0]->getMessage()];
            } else {
                $path = dirname($this->get('kernel')->getRootDir()).'/web/uploads';

                $fileName = uniqid().'.'.$file->guessExtension();

                if ($file->move($path, $fileName)) {
                    $data = ['status' => 'success', 'file' => $fileName];
                } else {
                    $data = ['status' => 'error', 'message' => $this->trans('upload_error')];
                }
            }
        }

        return new JsonResponse($data);
    }
}
