<?php
  header("Content-Type: application/json");
  
  // Simular API do servidor LAPS
   = ["action"] ?? ["action"] ?? "";
   = ["api_key"] ?? ["api_key"] ?? ["HTTP_X_API_KEY"] ?? "";
  
  // Verificar API key
  if ( !== "5deeb8a3-e591-4bd4-8bfb-f9d8b117844c") {
      http_response_code(401);
      echo json_encode(["error" => "Invalid API key"]);
      exit;
  }
  
  switch () {
      case "status":
          echo json_encode([
              "success" => true,
              "version" => "1.0.0",
              "status" => "running"
          ]);
          break;
          
      case "get_password":
           = ["computer"] ?? ["computer"] ?? "";
          if (empty()) {
              echo json_encode(["error" => "Computer name required"]);
              break;
          }
          
          // Simular senha LAPS
          echo json_encode([
              "success" => true,
              "password" => "TestPassword123!",
              "expiration_timestamp" => date("Y-m-d H:i:s", strtotime("+30 days")),
              "computer" => 
          ]);
          break;
          
      default:
          echo json_encode(["error" => "Unknown action"]);
  }
  ?>
