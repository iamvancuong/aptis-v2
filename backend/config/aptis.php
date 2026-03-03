<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exam Section Blueprints
    |--------------------------------------------------------------------------
    | Define which parts appear in a full mock test for each skill.
    | Parts can repeat (e.g. Reading Part 2 appears twice).
    */
    'exam_sections' => [
        'reading'   => [1, 2, 3, 4],
        'listening' => [1, 2, 3, 4],
        'writing'   => [1, 2, 3, 4],
        'speaking'  => [1, 2, 3, 4],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Duration (minutes)
    |--------------------------------------------------------------------------
    */
    'exam_duration' => [
        'reading'   => 35,
        'listening' => 35,
        'writing'   => 50,
        'speaking'  => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Part Counts
    |--------------------------------------------------------------------------
    | Define how many sets/questions should be picked for each part.
    | Defaults to 1 if not specified.
    |*/
    'exam_part_counts' => [
        'listening' => [
            1 => 13,   // 13 random MC questions
            2 => 1,    // 1 random speaker-matching question
            3 => 1,    // 1 random man/woman/both question
            4 => 2,    // 2 random passage questions
        ],
        'reading' => [
            1 => 1,
            2 => 2,
            3 => 1,
            4 => 1,
        ],
    ],
];
