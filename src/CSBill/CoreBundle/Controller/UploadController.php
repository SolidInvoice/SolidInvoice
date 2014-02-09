<?php

namespace CSBill\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Image;

class UploadController extends BaseController
{
    /**
     * @param Request        $request
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

                $file_name = uniqid() . '.' . $file->guessExtension();

                if ($file->move($path, $file_name)) {
                    $data = array('status' => 'success', 'file' => $file_name );
                } else {
                    $data = array('status' => 'error', 'message' => $this->trans('upload_error'));
                }
            }
        }

        return new JsonResponse($data);
    }
}
