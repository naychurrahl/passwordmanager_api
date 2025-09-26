<?php

  header('Content-Type: application/json');

  //require_once(__DIR__.'/Database.php');

  //Database
  $hostname = "localhost";
  $username = "root";
  $password = "";
  $database = "pman";

  $pdo = "mysql:host={$hostname};dbname={$database};charset=UTF8";

  try {
    $con = new PDO($pdo, $username, $password);
  } catch (PDOException $e) {
    die("ERROR! -> {$e->getMessage()}");
  }

  //Get request body
  switch(True){
    case ! empty($_POST):
      $requestBody = $_POST;
      break;

    case ! empty($_GET):
      $requestBody = $_GET;
      break;

    default:
      $requestBody = (array) json_decode(file_get_contents("php://input"), true);
  }

  //declare variables
  $id = $requestBody["puid"] ?? null;
  $host = $requestBody["host"] ?? null;
  $username = $requestBody["username"] ?? null;
  $password = $requestBody["password"] ?? null;

  // Get method and URI

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'DELETE':
      if (! $id)
      {
        http_response_code(400);
        die(json_encode("Puid is required"));
      }

      try {
        //code...
        $stmt = $con->prepare("DELETE FROM `data` WHERE `puid` = :id");
        $stmt->execute([':id' => $id]);

        die (json_encode('Success'));

      } catch (\Throwable $th) {
        http_response_code(500);
        die (json_encode(["Error:" => $th -> getMessage()]));
      }

      break;

    case 'PATCH':

      if (! $id)
      {
        http_response_code(400);
        die(json_encode("Puid is required"));
      }

      
      try {
        $sql = "UPDATE `data` SET `puid` = :puid";
  
        if ($host)
        {
            
          $sql .= ", `servicename` = :host";
          $bind['host'] = $host;
        }
  
        if (isset($username))
        {
          $sql .= ", `username` = :username";
          $bind['username'] = $username;
        }
  
        if (isset($password))
        {
          $sql .= ", `passkey` = :passkey";
          $bind['passkey'] = $password;
        }
        
        $sql .= " WHERE `puid` = :id ";
  
        $bind['id'] = $id;
        $bind['puid'] = $id;
        
        //code...
        $stmt = $con->prepare($sql);
  
        $stmt->execute($bind);
        
        die (json_encode("Success"));
      } catch (\Throwable $th) {
        http_response_code(500);
        die (json_encode(["Error:" => $th -> getMessage()]));
      }

    case 'POST':
      if (! $host || !(isset($username) || isset($password))) die(json_encode("Requires 'host' AND either 'username', 'password' or both"));
      
      //die(json_encode("Success"));

      # check if host and username exist
      try {
        $stmt = $con -> prepare("
          SELECT puid FROM data
          WHERE servicename = :host
          AND username = :username
          LIMIT 1
        ");

        $stmt->execute(['host' => $host, 'username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_COLUMN);
        
        # if exist, return code 200 creation failed, reason exist

        if ($data) die(json_encode(["existing" => $data]));
        
        # else if not exist, create
        $stmt = $con->prepare("
          INSERT INTO data (puid, servicename, username, passkey)
          VALUES (:puid, :host, :username, :password)
        ");
        
        $stmt->execute([

          'puid' => uniqid("__"),
          'host' => $host,
          'username' => $username,
          'password' => $password

        ]);

        # if create success, return success
        die(json_encode("Success"));

      } catch (\Throwable $th) {

        # else if create failed, return error
        http_response_code(500);
        die (json_encode(["Error:" => $th -> getMessage()]));
      }
      break;

    case 'GET':
      $stmt = $con -> prepare("
        SELECT puid as id,
        servicename as HOST,
        username as 'User Name',
        passkey as Password
        FROM data
        ORDER BY servicename DESC
      ");

      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      die (json_encode((array)$data));
    
    default:
      http_response_code(405);
      die (json_encode("Method not allowed: Use GET, POST or DELETE"));
  }

  
