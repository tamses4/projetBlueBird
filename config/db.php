<?php
// Fichier: app/core/Database.php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    // On ne met plus les valeurs en dur ici, on les définira dans la méthode getConnection
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port; // IMPORTANT : Ajout de la variable port
    public $conn;

    // Méthode pour obtenir la connexion
    public function getConnection() {
        $this->conn = null;

        // --- C'est ici que la magie opère ---
        // On demande à PHP : "Est-ce qu'il y a une variable d'environnement DB_HOST ?"
        // Si OUI (sur Render), on l'utilise.
        // Si NON (sur votre PC), on utilise "localhost".
        
        $this->host     = getenv('DB_HOST') ?: "localhost";
        $this->db_name  = getenv('DB_NAME') ?: "gestion_bus";
        $this->username = getenv('DB_USER') ?: "root";
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "Callita4";
        $this->port     = getenv('DB_PORT') ?: 3306; // Par défaut 3306 sur WAMP, mais change sur Aiven

        try {
            // Construction de la chaîne de connexion (DSN) avec le PORT inclus
            // "mysql:host=...;port=...;dbname=..."
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";

            $this->conn = new PDO(
                $dsn, 
                $this->username, 
                $this->password
            );
            
            // Configuration des options PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Note : "set names utf8" est déjà géré par ";charset=utf8" dans le DSN, 
            // mais on peut le garder pour être sûr.
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>