<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class JariyahModel extends Model
{
    protected $table = 'jariyah';
    protected $primaryKey = 'id_jariyah';
    protected $allowedFields = ['no_keuangan','id_user','keterangan_jariyah','nominal_jariyah','deskripsi_jariyah','status_infak'];
}