<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductImportController extends Controller
{
    public function showForm()
    {
        return view('import-products');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240'
        ]);
        
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Saltar la primera línea (encabezados)
        $headers = fgetcsv($handle, 0, ',');
        
        $imported = 0;
        $updated = 0;
        
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            // Saltar filas vacías
            if (empty($data[0]) && empty($data[1])) {
                continue;
            }
            
            // Mapeo para CSV de 5 columnas
            $productName = trim($data[0] ?? '');
            $price = floatval($data[1] ?? 0);
            $stock = intval($data[2] ?? 0);
            $beneficio = trim($data[3] ?? '');
            $psicologia = trim($data[4] ?? '');
            $unit = 'kg';
            
            if (empty($productName)) {
                continue;
            }
            
            // Buscar si ya existe
            $existingProduct = Product::where('name', $productName)->first();
            
            if ($existingProduct) {
                $existingProduct->update([
                    'price' => $price,
                    'stock' => $stock,
                    'unit' => $unit,
                    'beneficio' => $beneficio,
                    'psicologia_venta' => $psicologia,
                ]);
                $updated++;
            } else {
                Product::create([
                    'name' => $productName,
                    'price' => $price,
                    'stock' => $stock,
                    'unit' => $unit,
                    'beneficio' => $beneficio,
                    'psicologia_venta' => $psicologia,
                ]);
                $imported++;
            }
        }
        
        fclose($handle);
        
        return redirect('/dashboard')->with('success', "✅ Importación completada: $imported nuevos, $updated actualizados");
    }
}