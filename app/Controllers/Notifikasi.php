<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\KeuanganModel;
use App\Models\SaldoModel;
use App\Models\DonaturModel;
use App\Models\InfakModel;
use CodeIgniter\I18n\Time;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Notifikasi extends ResourceController
{
    public function index(){
        // header('Content-type: application/json');
        // // Set your Merchant Server Key
        // \Midtrans\Config::$serverKey = 'SB-Mid-server-Us4qHUjYvUBZPQKrSrSJr37y';
        // // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        // \Midtrans\Config::$isProduction = false;
        // // Set sanitization on (default)
        // \Midtrans\Config::$isSanitized = true;
        // // Set 3DS transaction for credit card to true
        // \Midtrans\Config::$is3ds = true;

        try {
            $json_result = file_get_contents('php://input');
            $result = json_decode($json_result, true);
            $status_code = $result['status_code'];
            $no_keuangan  = $result['order_id'];
            $nominal_infak  = round($result['gross_amount']);
            $ket_infak  = $result['custom_field1'];
            $des_infak  = $result['custom_field2'];
            $token = $result['custom_field3'];
            
    
            if ($status_code == 201){
                return $this->createInfak($token, $no_keuangan, $ket_infak, $nominal_infak, $des_infak, "Tunda");
            } else if ($status_code == 200){
                return $this->updateInfak($token, $no_keuangan, $ket_infak, $nominal_infak, $des_infak, "Selesai");
            }
        } catch(\Exception $e){
            return $this->fail('Tidak dapat memasukkan data keuangan');
        }
    }

    public function getSaldo()
    {
        $saldomodel = new SaldoModel();
        $datasaldo = $saldomodel->find(1);
        return $datasaldo['jml_saldo'];
    }

    public function updateSaldo($tipe, $nominal)
    {
        $model = new SaldoModel();
        if($tipe == "Pemasukan"){
            $data = [
                'jml_saldo' => $this->getSaldo()+$nominal,
                'update_at' => $this->getTime()
            ];
        }else{
            $data = [
                'jml_saldo' => $this->getSaldo()-$nominal,
                'update_at' => $this->getTime()
            ];
        }
        // Insert to Database
        $model->update(1, $data);
        $response = [
            'status'   => 200,
            'error'    => null,
            'messages' => [
                'success' => 'Data Updated'
            ]
        ];
        return $this->respond($response);
    }

    public function createInfak($token, $no_keuangan, $ket_infak, $nominal_infak,  $des_infak, $status_infak)
    {
        try {
            $key = getenv('TOKEN_SECRET');
            $model = new InfakModel();
            $modelkeu = new KeuanganModel();
            $modelkeu->getNomorKeuangan();
            $decoded = JWT::decode($token, new Key ($key, 'HS256'));
            $idusr = $decoded->id;
        } catch(\Exception $e){
            return $this->fail('Invalid token 1'.$idusr);
        }
            try {
                $data = [
                    'no_keuangan' => $no_keuangan,
                    'id_user' => $idusr,
                    'keterangan_infak' => $ket_infak,
                    'nominal_infak' => $nominal_infak,
                    'deskripsi_infak' => $des_infak,
                    'status_infak' => $status_infak
                ];
                // $data = json_decode(file_get_contents("php://input"));
                // $data = $this->request->getPost();
                $model->insert($data);
                $response = [
                    'status'   => 201,
                    'error'    => null,
                    'messages' => [
                        'success' => 'Data Saved'
                    ]
                ];
         
                return $this->respondCreated($response);
            } catch(\Exception $e){
                return $this->fail('Invalid token 2');
            }
        
    }

    public function updateInfak($token, $no_keuangan, $ket_infak, $nominal_infak,  $des_infak, $status_infak){
        $key = getenv('TOKEN_SECRET');
        $model = new InfakModel();
        $ada = $model->getWhere(['no_keuangan' => $no_keuangan])->getResult();
        $data = json_decode($ada, true);
        $id = $data['id_infak'];
        $decoded = JWT::decode($token, new Key ($key, 'HS256'));
        $id_usr = $decoded->id;
        if ($ada){            
            $data = [
                'no_keuangan' => $no_keuangan,
                'id_user' => $id_usr,
                'keterangan_infak' => $ket_infak,
                'nominal_infak' => $nominal_infak,
                'deskripsi_infak' => $des_infak,
                'status_infak' => $status_infak
            ];
                    
            // Insert to Database
            $model->update($id, $data);
            return $this->createKeuangan($no_keuangan, $ket_infak, $nominal_infak, $des_infak);
            $response = [
                'status'   => 200,
                'error'    => null,
                'messages' => [
                'success' => 'Data Updated'
                ]
            ];
            return $this->respond($response);
        } else {
            return $this->failNotFound('No Data Found with id '.$id);
        }
    }

    public function createKeuangan($no_keuangan, $ket_infak,  $nominal, $des_infak)
    {
        try {
            $model = new KeuanganModel();
            $jmlkasakhir = $this->getSaldo() + $nominal;
                $data = [
                    'no_keuangan' => $no_keuangan,
                    'tipe_keuangan' => "Pemasukan",
                    'tgl_keuangan' => $this->getTime(),
                    'keterangan_keuangan' => "Infaq atas nama ".$ket_infak,
                    'jenis_keuangan' => "Lain-lain",
                    'status_keuangan' => "Selesai",
                    'nominal_keuangan' => $nominal,
                    'jml_kas_awal' => $this->getSaldo(),
                    'jml_kas_akhir' => $jmlkasakhir,
                    'deskripsi_keuangan' => $des_infak,
                    'create_at' => $this->getTime(),
                    'update_at' => $this->getTime()
                ];
                // $data = json_decode(file_get_contents("php://input"));
                // $data = $this->request->getPost();
                $model->insert($data);
                $this->updateSaldo("Pemasukan", $nominal);
                $response = [
                    'status'   => 201,
                    'error'    => null,
                    'messages' => [
                        'success' => 'Data Saved'
                    ]
                ];
         
                return $this->respondCreated($response);
            } catch(\Exception $e){
                return $this->fail('Tidak dapat menyimpan data keuangan');
            }
        
    }

    public function getTime(){
        $myTime = Time::now('Asia/Jakarta', 'id_ID');
        return $myTime->toDateString();
    }
}