<?php

namespace CSBill\CoreBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Image;

class UploadController extends Controller
{
    public function imageUploadAction()
    {
        $request = $this->getRequest();
        $file = $request->files->get('Filedata');

        if ($file === null) {
            $data = array('status' => 'error', 'message' => $this->trans('Invalid File'));
        } else {
            $imageconstraint = new Image(array('maxSize' => "1024k"));
            $errors = $this->get('validator')->validateValue($file, $imageconstraint);

            if (count($errors) > 0) {
                $data = array('status' => 'error', 'message' => $errors[0]->getMessage());
            } else {

                $path = dirname($this->get('kernel')->getRootDir()).'/web/uploads';

                $file_name = uniqid().'.'.$file->guessExtension();

                if ($file->move($path, $file_name)) {
                    $data = array('status' => 'success', 'file' => $file_name);
                } else {
                    $data = array('status' => 'error', 'message' => $this->trans('There was an error uploading the file'));
                }
            }
        }

        $response = new JsonResponse($data);
        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
