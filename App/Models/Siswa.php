<?php

namespace App\Models;

use System\Model;
use System\Crayner\Database\DB;


class Siswa extends Model
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function getDataSiswa(){
		return DB::table("siswa")->select()->get();
	}	
}