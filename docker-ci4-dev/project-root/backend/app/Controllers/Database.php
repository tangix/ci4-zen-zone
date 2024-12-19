<?php

namespace App\Controllers;

class Database extends BaseController
{

    public function index()
    {
        $db = db_connect();

        $db
            ->table('example')
            ->insert([
                'message' => 'Message was added on ' . date('c')
            ]);

        $query = $db
            ->table('example')
            ->orderBy('id', 'DESC')
            ->get();

        $parser = service('parser');

        return $parser->setData([
            'messages' => $query->getResultArray()
        ])->render('database_table');
    }

}
