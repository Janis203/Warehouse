<?php
require "vendor/autoload.php";

use App\Products;

$tries = 0;
$data = json_decode(file_get_contents("users.json"));
while ($tries < 3) {
    $password = readline("Enter password: ");
    $userFound = false;
    foreach ($data->users as $user) {
        if ($password === $user->password) {
            echo "Welcome $user->username" . PHP_EOL;
            $userFound = true;
            break;
        }
    }
    $tries++;
    if (!$userFound) {
        echo "Incorrect password " . PHP_EOL;
    } else {
        break;
    }
    if ($tries === 3) {
        exit("Too many tries");
    }
}

$product = new Products("products.json");
while (true) {
    echo "Enter choice: 
[1] Create product
[2] Edit product
[3] Delete product
[4] Display products
[5] Check logs
[Any key] Exit
______________________" . PHP_EOL;
    $choice = (int)readline();
    switch ($choice) {
        case 1:
            $product->create();
            break;
        case 2:
            $product->edit();
            break;
        case 3:
            $product->delete();
            break;
        case 4:
            $product->display();
            break;
        case 5:
            $product->logs();
            break;
        default:
            exit("Goodbye");
    }
}