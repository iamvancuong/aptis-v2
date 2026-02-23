<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Set;
use App\Models\Question;
use Illuminate\Database\Seeder;

class SetSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = Quiz::all();

        foreach ($quizzes as $quiz) {
            // Only create Cohesive Sets for Writing Part 1. Parts 2, 3, 4 exist but hold no separate sets.
            if ($quiz->skill === 'writing' && in_array($quiz->part, [2, 3, 4])) {
                continue;
            }

            for ($i = 1; $i <= 3; $i++) {
                $set = Set::firstOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'order' => $i - 1,
                    ],
                    [
                        'title' => "{$quiz->title} - Bộ {$i}",
                        'is_public' => true,
                    ]
                );

                // Create dummy questions based on skill/part
                if ($set->questions()->count() === 0) {
                    $this->createQuestionsForSet($set, $quiz);
                }
            }
        }
    }

    private function createQuestionsForSet(Set $set, Quiz $quiz)
    {
        // Dispatch to specific handlers based on Part
        if ($quiz->skill === 'reading') {
            match ($quiz->part) {
                1 => $this->createReadingPart1($set, $quiz),
                2 => $this->createReadingPart2($set, $quiz),
                3 => $this->createReadingPart3($set, $quiz),
                4 => $this->createReadingPart4($set, $quiz),
                default => null,
            };
        }

        if ($quiz->skill === 'listening') {
            match ($quiz->part) {
                1 => $this->createListeningPart1($set, $quiz),
                2 => $this->createListeningPart2($set, $quiz),
                3 => $this->createListeningPart3($set, $quiz),
                4 => $this->createListeningPart4($set, $quiz),
                default => null,
            };
        }

        if ($quiz->skill === 'writing') {
            match ($quiz->part) {
                1 => $this->createWritingFullTest($set, $quiz, $set->order),
                default => null,
            };
        }
    }

    private function createReadingPart1(Set $set, Quiz $quiz)
    {
        // Part 1: Sentence Gap Fill (5 Questions per Set - Standard APTIS)
        // Refactored to be a SINGLE Question record containing 5 items
        
        $paragraphs = [];
        $choices = [];
        $correctAnswers = [];

        for ($i = 1; $i <= 5; $i++) {
            $options = ['buy', 'eat', 'sleep'];
            $correctIndex = array_rand($options); // 0, 1, or 2

            // Build metadata arrays
            $paragraphs[] = "Question {$i}: The cat wants to [BLANK] fish.";
            $choices[] = $options;
            $correctAnswers[] = $correctIndex;
        }

        $metadata = [
            'paragraphs' => $paragraphs,
            'choices' => $choices, 
            'correct_answers' => $correctAnswers,
            'explanation' => "The correct answers fit the context."
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $quiz->part,
            'type' => 'reading-part-1',
            'title' => "Reading Part 1 - Sentence Completion", // Single Title
            'stem' => "Choose the best word to complete each sentence.",
            'point' => 5, // 5 points total
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createReadingPart2(Set $set, Quiz $quiz)
    {
        // Part 2: Paragraph Ordering (1 Question per Set)
        // Metadata: sentences (In correct order)
        
        $sentences = [
            "We decided to go to the beach.", // Sentence 1 (Fixed)
            "First, we packed our bags.",
            "Then, we drove for two hours.",
            "When we arrived, the sun was shining.",
            "We swam in the sea all afternoon.",
            "Finally, we went home tired but happy."
        ];

        $metadata = [
            'sentences' => $sentences
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $quiz->part,
            'type' => 'reading-part-2',
            'title' => "Reading Part 2 - Ordering",
            'stem' => "Order the sentences to make a story. The first sentence is already in place.",
            'point' => 5,
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createReadingPart3(Set $set, Quiz $quiz)
    {
        // Part 3: Short Text Comprehension (1 Question per Set)
        // Metadata: options (4 texts), questions (7 items), correct_answers
        
        $options = [
            "Person A: I love hiking in the mountains because it's peaceful.",
            "Person B: I prefer the beach. The sound of waves relaxes me.",
            "Person C: City breaks are the best. I enjoy museums and cafes.",
            "Person D: I don't like traveling. I prefer staying at home with a book."
        ];

        $subQuestions = [
            "Who likes the mountains?",
            "Who enjoys the seaside?",
            "Who prefers urban environments?",
            "Who is a homebody?",
            "Who mentions museums?",
            "Who finds waves relaxing?",
            "Who thinks hiking is peaceful?"
        ];

        // Map correct answers indices (A=0, B=1, C=2, D=3)
        $correctAnswers = [0, 1, 2, 3, 2, 1, 0];

        $metadata = [
            'options' => $options,
            'questions' => $subQuestions,
            'correct_answers' => $correctAnswers
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $quiz->part,
            'type' => 'reading-part-3',
            'title' => "Reading Part 3 - Opinion Matching",
            'stem' => "Read the four opinions and match them to the questions.",
            'point' => 7,
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createReadingPart4(Set $set, Quiz $quiz)
    {
        // Part 4: Long Text Comprehension (1 Question per Set)
        // Metadata: headings (8), paragraphs (7), correct_answers
        
        $headings = [
            "The Early Years",
            "Career Beginnings",
            "Rise to Fame",
            "Personal Struggles",
            "The Comeback",
            "Legacy",
            "Awards and Neighbors", // Distractor
            "Future Plans"
        ];

        $paragraphs = [
            "John started playing guitar/piano when he was five years old...",
            "His first job was at a local record store where he met...",
            "His second album topped the charts in three countries...",
            "However, fame brought pressure and he took a break...",
            "Five years later, he returned with a new sound...",
            "Today, he is considered one of the most influential...",
            "He plans to retire next year and travel the world..."
        ];

        // Map correct heading indices
        $correctAnswers = [0, 1, 2, 3, 4, 5, 7]; // Matching headings

        $metadata = [
            'headings' => $headings,
            'paragraphs' => $paragraphs,
            'correct_answers' => $correctAnswers
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $quiz->part,
            'type' => 'reading-part-4',
            'title' => "Reading Part 4 - Heading Matching",
            'stem' => "Match the headings to the paragraphs. There is one extra heading.",
            'point' => 7,
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function getQuestionType($skill, $part)
    {
        return "{$skill}-part-{$part}";
    }

    // ========== LISTENING SEEDERS ==========

    private function createListeningPart1(Set $set, Quiz $quiz)
    {
        // Part 1: Short Audio MCQ (3 choices, 1 correct)
        $metadata = [
            'choices' => [
                '10:00',
                '10:15',
                '11:15',
            ],
            'correct_answer' => 1,
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'listening',
            'part' => 1,
            'type' => 'listening-part-1',
            'title' => 'Listening Part 1 - Short Audio',
            'stem' => 'A woman is talking to her coworker. When does the meeting start?',
            'point' => 1,
            'order' => 1,
            'metadata' => $metadata,
            'audio_path' => null,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createListeningPart2(Set $set, Quiz $quiz)
    {
        // Part 2: Conversation (4 speakers, 6 opinions, matching)
        $metadata = [
            'items' => ['Speaker A', 'Speaker B', 'Speaker C', 'Speaker D'],
            'audio_files' => [null, null, null, null],
            'choices' => [
                'Should recycle more',
                'Thinks climate change is exaggerated',
                'Wants to use public transport',
                'Believes in renewable energy',
                'Thinks individual actions don\'t matter',
                'Supports local farming',
            ],
            'correct_answers' => [0, 2, 3, 5],
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'listening',
            'part' => 2,
            'type' => 'listening-part-2',
            'title' => 'Listening Part 2 - Conversation',
            'stem' => 'Topic 1: Protecting the Environment - choose the correct description.',
            'point' => 4,
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createListeningPart3(Set $set, Quiz $quiz)
    {
        // Part 3: Monologue (shared choices, 4 statements)
        $metadata = [
            'topic' => 'politics bản 1',
            'shared_choices' => [
                'Agree',
                'Disagree',
                'Not stated',
            ],
            'statements' => [
                'Young people are becoming more interested in politics',
                'Social media has changed political activism',
                'People are better informed about political issues',
                'More women are likely to participate in politics',
            ],
            'correct_answers' => [0, 0, 2, 1],
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'listening',
            'part' => 3,
            'type' => 'listening-part-3',
            'title' => 'Listening Part 3 - Monologue',
            'stem' => 'Listen and choose the correct option for each statement.',
            'point' => 4,
            'order' => 1,
            'metadata' => $metadata,
            'audio_path' => null,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createListeningPart4(Set $set, Quiz $quiz)
    {
        // Part 4: Complex Audio (2 MCQ sub-questions)
        $metadata = [
            'topic' => 'Personal finances',
            'questions' => [
                [
                    'question' => 'What should you do to better control your short-term spending?',
                    'choices' => [
                        'Avoid all unnecessary purchases entirely',
                        'Monitor your spending for a weekly plan',
                        'Use only cash instead of cards',
                    ],
                ],
                [
                    'question' => 'What does the speaker suggest for improving financial management?',
                    'choices' => [
                        'Seek advice from someone who is experienced',
                        'Invest in more financial apps',
                        'Avoid talking about money with friends',
                    ],
                ],
            ],
            'correct_answers' => [1, 0],
        ];

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'listening',
            'part' => 4,
            'type' => 'listening-part-4',
            'title' => 'Listening Part 4 - Complex Audio',
            'stem' => 'Listen to the talk about personal finances and answer the questions.',
            'point' => 2,
            'order' => 1,
            'metadata' => $metadata,
            'audio_path' => null,
        ]);
        $set->questions()->attach($question->id);
    }
    private function createWritingFullTest(Set $set, Quiz $quiz, int $setOrder)
    {
        // Define 3 cohesive themes based on the set order (0, 1, 2)
        $themes = [
            0 => [
                'name' => 'Sports Club',
                'p1_context' => 'You want to join a sports club.',
                'p2_scenario' => 'You are a new member of the sports club. Write an email to your friend telling them about the club.',
                'p3_prompts' => [
                    "A member asks: 'I've never played sports before. Is this club suitable for beginners?'",
                    "Another member posts: 'The changing rooms are always dirty. Does anyone else agree?'",
                    "The manager posts: 'We are planning a social event next month. Any suggestions?'"
                ],
                'p4_context' => "You received an email from the club manager about a new membership fee increase.",
                'p4_email' => "Dear Member,\n\nDue to rising maintenance costs, we must increase the monthly membership fee by 15% starting next month. We hope you understand and continue to enjoy our facilities.",
                'p4_task1' => "Write an email to a friend who is also a member. Express your feelings about the increase.",
                'p4_task2' => "Write a formal email to the club manager. Express your dissatisfaction and suggest an alternative solution (e.g., reducing hours instead)."
            ],
            1 => [
                'name' => 'Photography Course',
                'p1_context' => 'You want to enroll in a weekend photography course.',
                'p2_scenario' => 'You just had your first photography class. Write to a friend about what you learned.',
                'p3_prompts' => [
                    "A student asks: 'What kind of camera should a beginner buy?'",
                    "A student complains: 'The instructor speaks too fast and I can't follow the technical terms.'",
                    "The instructor posts: 'We are going on a field trip for landscape photography. Where should we go?'"
                ],
                'p4_context' => "You received an email from the course coordinator.",
                'p4_email' => "Dear Student,\n\nUnfortunately, the upcoming field trip has been canceled due to bad weather. We will refund the bus fee or offer a studio session instead. Please vote on your preference.",
                'p4_task1' => "Write an informal email to your classmate discussing the cancellation and which option you prefer.",
                'p4_task2' => "Write a formal email to the coordinator stating your preference and asking for more details about the studio session."
            ],
            2 => [
                'name' => 'Technology Expo',
                'p1_context' => 'You are registering as a volunteer for an upcoming Technology Expo.',
                'p2_scenario' => 'You have been accepted as a volunteer. Write an email to a friend sharing the good news.',
                'p3_prompts' => [
                    "A volunteer asks: 'What is the dress code for the event?'",
                    "Someone posts: 'I heard we have to work 10 hours a day. That seems too exhausting.'",
                    "The organizer asks: 'We need ideas to keep the crowd entertained while waiting in line.'"
                ],
                'p4_context' => "You received an email from the head of the volunteer committee.",
                'p4_email' => "Dear Volunteer,\n\nDue to unexpectedly high ticket sales, we need all volunteers to arrive 2 hours earlier than previously scheduled. We know this is an inconvenience, but we count on your support.",
                'p4_task1' => "Write an informal email to a fellow volunteer complaining about the early start.",
                'p4_task2' => "Write a formal email to the committee head. Explain why arriving 2 hours early is difficult for you, but offer a compromise."
            ],
        ];

        $theme = $themes[$setOrder] ?? $themes[0];

        // Part 1: Form Filling
        Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => 1,
            'type' => 'writing-part-1',
            'title' => "{$theme['name']} - Part 1 (Form Filling)",
            'stem' => "Please fill in the form below.",
            'point' => 5,
            'order' => 1,
            'metadata' => [
                'instructions' => $theme['p1_context'] . " Fill in the form.",
                'fields' => [
                    ['label' => 'Full Name', 'placeholder' => 'e.g. Nguyen Van A'],
                    ['label' => 'Date of Birth', 'placeholder' => 'e.g. 01/01/1990'],
                    ['label' => 'Interests', 'placeholder' => 'Briefly state your interests.'],
                ],
            ]
        ])->sets()->attach($set->id);

        // Part 2: Email
        Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => 2,
            'type' => 'writing-part-2',
            'title' => "{$theme['name']} - Part 2 (Email)",
            'stem' => "Write a short email.",
            'point' => 5,
            'order' => 2,
            'metadata' => [
                'scenario' => $theme['p2_scenario'],
                'word_limit' => ['min' => 20, 'max' => 30],
                'hints' => "Write roughly 20-30 words.",
            ]
        ])->sets()->attach($set->id);

        // Part 3: Social Response
        Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => 3,
            'type' => 'writing-part-3',
            'title' => "{$theme['name']} - Part 3 (Social Media)",
            'stem' => "Respond to the messages in the group.",
            'point' => 10,
            'order' => 3, 
            'metadata' => [
                'questions' => array_map(fn($prompt) => [
                    'prompt' => $prompt,
                    'word_limit' => ['min' => 30, 'max' => 40]
                ], $theme['p3_prompts']),
            ]
        ])->sets()->attach($set->id);

        // Part 4: Dual Email
        Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => 4,
            'type' => 'writing-part-4',
            'title' => "{$theme['name']} - Part 4 (Formal & Informal Mails)",
            'stem' => "Read the email and complete the two tasks.",
            'point' => 20, 
            'order' => 4,
            'metadata' => [
                'context' => $theme['p4_context'],
                'email' => [
                    'greeting' => "Dear Member,",
                    'body' => $theme['p4_email'],
                    'sign_off' => "Best regards,\nThe Management"
                ],
                'task1' => [
                    'instruction' => $theme['p4_task1'] . " Write about 50 words.",
                    'word_limit' => ['min' => 40, 'max' => 50],
                ],
                'task2' => [
                    'instruction' => $theme['p4_task2'] . " Write 120-150 words.",
                    'word_limit' => ['min' => 120, 'max' => 150],
                ]
            ]
        ])->sets()->attach($set->id);
    }
}
