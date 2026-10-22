<?php

use App\Models\Department;

it('finds department illustrations from alternate tags and names', function (string $tag, string $name, string $image) {
    $department = Department::factory()->make([
        'code' => 'CUSTOM',
        'tag' => $tag,
        'name' => $name,
    ]);

    expect($department->image_url)
        ->toEndWith('/images/departments/'.$image);
})->with([
    ['Administration', 'Administration & Finances', '01-administration.png'],
    ['Sûreté', 'Département Sûreté', '03-securite.png'],
]);
