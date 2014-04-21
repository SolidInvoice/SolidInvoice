<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
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
     * @param  Request      $request
     * @return JsonResponse
     */
    public function imageUploadAction(Request $request)
    {
        $file = $request->files->get('Filedata');

        if ($file === null) {
            $data = array('status' => 'error', 'message' => $this->trans('invalid_file'));
        } else {
            $errors = $this->get('validator')->validateValue($file, new Image(array('maxSize' => "1024k")));

            if (count($errors) > 0) {
                $data = array('status' => 'error', 'message' => $errors[0]->getMessage());
            } else {

                $path = dirname($this->get('kernel')->getRootDir()) . '/web/uploads';

                $fileName = uniqid() . '.' . $file->guessExtension();

                if ($file->move($path, $fileName)) {
                    $data = array('status' => 'success', 'file' => $fileName );
                } else {
                    $data = array('status' => 'error', 'message' => $this->trans('upload_error'));
                }
            }
        }

        return new JsonResponse($data);
    }
}
