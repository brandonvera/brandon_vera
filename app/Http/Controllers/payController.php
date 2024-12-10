<?php

namespace App\Http\Controllers;

use App\Models\Req;
use App\Models\Webhook;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class payController extends Controller
{
    //funcion para gestiionar pagos easy money
    public function receiveEasy(Request $request){
        //validacion de datos request
        $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
        ]);

        //enviando solicitud a api de easymoney por metodo http
        $response = Http::post('http://localhost:3000/process', [
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        //verificando si falla el response
        if (!$response->successful()) {
            $this->saveDataTransaction($request, 'failed', 'easymoney', ''); //guardando info de la transaccion
            $mess = $this->checkDecimal($request->amount); //en caso de falla verifica si fue por numeros decimales

            return response()->json(['message' => 'Error in easymoney transaction. ' . $mess], 400);      
        }


        $this->saveDataTransaction($request, 'success', 'easymoney', ''); //guardando info de la transaccion
        $this->saveReqInfo('http://localhost:3000/process', 'easymoney', json_encode($request)); //guardando log de la request
        return response()->json(['message' => 'easymoney transaction succesfully'], 200);
    }

    //metodo para gestionar superwalletz
    public function receiveSuper(Request $request){
        //validacion de datos request
        $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'callback_url' => 'required|url',
        ]);

        //enviando solicitud a api de superwalletz por metodo http
        $response = Http::post('http://localhost:3003/pay', [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'callback_url' => $request->callback_url,
        ]);

        if (!$response->successful()) {
            $this->saveDataTransaction($request, 'failed', 'superwalletz', ''); //guardando info de la transaccion
            return response()->json(['message' => 'Error in superwalletz transaction.'], 400); 
        }

        $this->saveDataTransaction($request, 'in progress', 'superwalletz', data_get($response->json(), 'transaction_id')); //guardando info de la transaccion
        $this->saveReqInfo('http://localhost:3003/pay', 'superwalletz', json_encode($request));//guardando log de la solicitud
        return response()->json([
            'message' => 'starting superwalletz transaction',
            'transaction Id' => data_get($response->json(), 'transaction_id')
        ], 200);
    }

    //metodo para gestionar webhook
    public function handleWebhook(Request $request){
        $data = $request->all(); //obteniendo datos del webhook
        $jsonData = json_encode($data);

        //creando log de info del webhook
        Webhook::create([
            'superwalletz_info' => $jsonData,
        ]); 

        //obteniendo registro de transaccion para verificar si existe el id generado del webhook
        $trx_register = Transaction::where('transactionId', $data['transaction_id'])->first();

        //en caso de no existir lanzar error
        if(!$trx_register)
                return response()->json(['message' => 'Transaction ' . $data['transaction_id'] . ' does not exist. '], 401);

        //dependiendo del status se setea el valor en bd
        ($data['status'] !== 'success')
            ? $trx_register->status = 'failed'
            : $trx_register->status = 'success';

        $trx_register->save(); //guardando en bd
        return response()->json(['message' => 'webhook is loaded.']);
    }

    //metodo para guardar datos de transaccion
    public function saveDataTransaction($data, $status, $wallet, $transactionId){
        //guardando datos en tabla de transacciones
        Transaction::create([
            'amount' => $data->amount,
            'currency' => $data->currency,
            'status' => $status,
            'wallet' => $wallet,
            'transactionId' => $transactionId,
        ]);
    }

    //guardar info de los request enviados
    public function saveReqInfo($url, $wallet, $req){
        //guardando datos en la tabla de requests
        Req::create([
            'url' => $url,
            'wallet' => $wallet,
            'request' => $req
        ]);
    }

    //metodo para verificar si el amount es decimal
    public function checkDecimal($amount){
        return strpos((string)$amount, '.')  
            ? ' amount have to be integer'
            : '';
    }
}