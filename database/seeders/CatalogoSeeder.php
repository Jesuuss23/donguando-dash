<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo;

class CatalogoSeeder extends Seeder
{
    public function run()
    {
        $categorias = [
            'General',
            'Carne de cuy',
            'Carne de conejo',
            'Carne de cerdo',
            'Carne de res',
            'Carne de caprino (cabra / cabrito)',
            'Carne de ovino (cordero)',
            'Carne de gallina',
            'Carne de pollo',
            'Carne de pato',
            'Pavo',
            'Camarón / langostino',
            'Trucha',
            'Miel y derivados (miel, polen, algarrobina)',
            'Huevos',
            'Quesos',
            'Caldo de hueso',
            'Aceitunas',
            'Embutidos (hamburguesas, nuggets, chorizo, etc.)',
            'Pastas / condimentos (ají, ajo, culantro, etc.)'
        ];
        
        foreach ($categorias as $index => $categoria) {
            Catalogo::updateOrCreate(
                ['slug' => \Str::slug($categoria)],
                [
                    'categoria' => $categoria,
                    'order' => $index
                ]
            );
        }
    }
}