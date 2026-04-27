<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    private $rowCount = 0;
    private $errors = [];
    
    public function model(array $row)
    {
        $this->rowCount++;
        
        // Buscar si ya existe un producto con el mismo nombre
        $existingProduct = Product::where('name', $row['producto'])->first();
        
        if ($existingProduct) {
            // Si existe, actualizar
            $existingProduct->update([
                'price' => $row['precio'] ?? $existingProduct->price,
                'stock' => $row['stock'] ?? $existingProduct->stock,
                'unit' => $row['unidad'] ?? $existingProduct->unit,
                'beneficio' => $row['beneficio_uso'] ?? $row['beneficio'] ?? $existingProduct->beneficio,
                'psicologia_venta' => $row['psicologia_de_venta'] ?? $row['psicologia_venta'] ?? $existingProduct->psicologia_venta,
            ]);
            return null;
        }
        
        // Si no existe, crear nuevo
        return new Product([
            'name' => $row['producto'] ?? $row['name'] ?? 'Sin nombre',
            'price' => $row['precio'] ?? $row['price'] ?? 0,
            'stock' => $row['stock'] ?? 0,
            'unit' => $row['unidad'] ?? $row['unit'] ?? 'kg',
            'beneficio' => $row['beneficio_uso'] ?? $row['beneficio'] ?? null,
            'psicologia_venta' => $row['psicologia_de_venta'] ?? $row['psicologia_venta'] ?? null,
        ]);
    }
    
    public function rules(): array
    {
        return [
            'producto' => 'nullable|string',
            'precio' => 'nullable|numeric',
            'stock' => 'nullable|integer',
        ];
    }
    
    public function getRowCount(): int
    {
        return $this->rowCount;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}