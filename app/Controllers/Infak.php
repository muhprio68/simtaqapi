<?php namespace App\Controllers;
 
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\InfakModel;
use CodeIgniter\I18n\Time;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
 
class Infak extends ResourceController
{
    use ResponseTrait;
    // get all infak
    public function index()
    {
        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
        try {
            $decoded = JWT::decode($token, new Key ($key, 'HS256'));
            $idusr = $decoded->uid;
            if ($idusr != null){
                $model = new InfakModel();
                $data = $model->findAll();
                return $this->respond($data, 200);
            } else {
                return $this->fail('User tidak terdaftar');
            }
        } catch (\Throwable $th) {
            return $this->fail('Invalid Token');
        }
    }
 
    // get single infak
    public function show($id = null)
    {
        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
        try {
            $decoded = JWT::decode($token, new Key ($key, 'HS256'));
            $idusr = $decoded->uid;
            if ($idusr != null){
                $model = new InfakModel();
                $data = $model->getWhere(['id_infak' => $id])->getResult();
                if($data){
                    return $this->respond($data);
                }else{
                    return $this->failNotFound('No Data Found with id '.$id);
                }
            } else {
                return $this->fail('User tidak terdaftar');
            }
        } catch (\Throwable $th) {
            return $this->fail('Invalid Token');
        }
    }

    // create infak
    public function create()
    {
    }
 
    // update infak
    public function update($id = null)
    {
    }
 
    // delete infak
    public function delete($id = null)
    {
        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
        try {
            $decoded = JWT::decode($token, new Key ($key, 'HS256'));
            $id_usr = $decoded->uid;
            if ($id_usr != null){
                $model = new InfakModel();
                $data = $model->find($id);
                if($data){
                    $model->delete($id);
                    $response = [
                        'status'   => 200,
                        'error'    => null,
                        'messages' => [
                            'success' => 'Data Deleted'
                        ]
                    ];
                    
                    return $this->respondDeleted($response);
                }else{
                    return $this->failNotFound('No Data Found with id '.$id);
                }
            } else {
                return $this->fail('User tidak terdaftar');
            }
        } catch (\Throwable $th) {
            return $this->fail('Invalid Token');
        }
         
    }

    public function getTime(){
        $myTime = Time::now('Asia/Jakarta', 'id_ID');
        return $myTime->toDateString();
    }
 
}