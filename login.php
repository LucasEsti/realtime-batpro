<?php
    //PHP pour formulaire d'inscription

    //Verification de la methode si la requete HTTP est définie
    $requestMethod = $_SERVER["REQUEST_METHOD"] ?? '';

    //Verifier si le formulaire a été soumis
    if ($requestMethod === "POST") {
        //Récuperer les valeurs des champs du formulaire
        $username = $_POST["username"] ?? '';
        $password = $_POST["password"] ?? '';

        //Verifier les identifiants (utilisateur et mots de passe)
        if ($username === 'admin' && $password === 'password') {
            //Rediriger vers la page admin
            header("Location: admin.php");
            exit; //Arriter l'execution du script après la redirection
        } else {
            //Afficher un message d'erreur
            $errorMessage = "Identifiant ou mots de passe incorrect";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style/style_login.css">
    <title>LiveChat</title>
</head>
<body>
    <div id="loginContainer">
        <h1>Login</h1>
        <form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Mots de passe:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Se Connecter</button>
        </form>
    </div>


    <?php
        //Afficher un message d'erreur s'il y en a un
        if (isset($errorMessage)) {
            echo "<p>$errorMessage</p>";
        }
    ?>
</body>
</html>