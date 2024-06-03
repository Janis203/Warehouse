<?php

namespace App;

use Carbon\Carbon;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class Products
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode(['last_id' => 0, 'products' => []], JSON_PRETTY_PRINT));
        }
    }

    private function loadProducts(): ?array
    {
        return json_decode(file_get_contents($this->file), true);
    }

    private function saveProducts(array $data): void
    {
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function create(): void
    {
        $data = $this->loadProducts();
        $id = ++$data['last_id'];
        $name = readline("Enter product name: ");
        $amount = readline("Enter amount: ");
        $created = Carbon::now()->toDateTimeString();
        $lastUpdate = Carbon::now()->toDateTimeString();
        $data['products'][] = ["id" => $id, "name" => $name, "amount" => $amount, "created" => $created, "updated" => $lastUpdate];
        $this->saveProducts($data);
    }

    private function logChanges(string $action, int $productID, array $changes = []): void
    {
        $logFile = "logs.json";
        if (!file_exists($logFile)) {
            file_put_contents($logFile, json_encode(['logs' => []], JSON_PRETTY_PRINT));
        }
        $logData = json_decode(file_get_contents($logFile), true);
        $logEntry = [
            "product_id" => $productID,
            "action" => $action,
            "changes" => $changes,
            "time" => Carbon::now()->toDateTimeString()
        ];
        $logData["logs"][] = $logEntry;
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    }

    public function edit(): void
    {
        $data = $this->loadProducts();
        if (empty($data["products"])) {
            echo "No products to edit " . PHP_EOL;
            return;
        }
        $edit = (int)readline("enter ID of product to edit: ");
        $productFound = false;
        foreach ($data["products"] as &$product) {
            if ($edit == $product["id"]) {
                $productFound = true;
                $changes = [];
                $changeName = strtolower(readline("Change product name (y/yes)? "));
                if ($changeName === "y" || $changeName === "yes") {
                    $newName = readline("Enter new name ");
                    if ($newName !== $product["name"]) {
                        $changes["name"] = ["old" => $product["name"], "new" => $newName];
                        $product["name"] = $newName;
                        $product["updated"] = Carbon::now()->toDateTimeString();
                    }
                }
                $changeAmount = strtolower(readline("Change product amount (y/yes)? "));
                if ($changeAmount === "y" || $changeAmount === "yes") {
                    $newAmount = (int)readline("Enter new amount ");
                    if ($newAmount !== $product["amount"]) {
                        $changes["amount"] = ["old" => $product["amount"], "new" => $newAmount];
                        $product["amount"] = $newAmount;
                        $product["updated"] = Carbon::now()->toDateTimeString();
                    }
                }
                if (!empty($changes)) {
                    $this->logChanges('edited', $edit, $changes);
                }
            }
        }
        if ($productFound) {
            $this->saveProducts($data);
        } else {
            echo "Product with ID $edit not found." . PHP_EOL;
        }
    }

    public function delete(): void
    {
        $data = $this->loadProducts();
        if (empty($data["products"])) {
            echo "No products to delete " . PHP_EOL;
            return;
        }
        $delete = (int)readline("Enter product ID to delete ");
        $productFound = false;
        foreach ($data["products"] as $index => $product) {
            if ($delete === $product["id"]) {
                $productFound = true;
                $deletedProduct = $product;
                array_splice($data["products"], $index, 1);
                $this->logChanges("deleted", $delete, ["deleted_product" => $deletedProduct]);
                break;
            }
        }
        if ($productFound) {
            $this->saveProducts($data);
            echo "Product with ID $delete has been deleted." . PHP_EOL;
        } else {
            echo "Product with ID $delete not found." . PHP_EOL;
        }
    }

    public function display(): void
    {
        $data = $this->loadProducts();
        if (empty($data["products"])) {
            echo "No products found " . PHP_EOL;
            return;
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(["ID", "Name", "Amount", "Created", "Updated"]);
        foreach ($data["products"] as $product) {
            $table->addRow([
                $product["id"],
                $product["name"],
                $product["amount"],
                $product["created"],
                $product["updated"]
            ]);
        }
        $table->render();
    }

    public function logs(): void
    {
        $logFile = "logs.json";
        if (!file_exists($logFile)) {
            echo "No logs found" . PHP_EOL;
            return;
        }
        $logData = json_decode(file_get_contents($logFile), true);
        if (empty($logData["logs"])) {
            echo "No logs found" . PHP_EOL;
            return;
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(["ID", "Action", "Changes", "Time"]);
        foreach ($logData["logs"] as $log) {
            $changes = !empty($log["changes"]) ? json_encode($log["changes"]) : "N/A";
            $table->addRow([
                $log["product_id"],
                $log["action"],
                $changes,
                $log["time"]
            ]);
        }
        $table->render();
    }
}