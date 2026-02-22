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
                1 => $this->createWritingPart1($set, $quiz),
                2 => $this->createWritingPart2($set, $quiz),
                3 => $this->createWritingPart3($set, $quiz),
                4 => $this->createWritingPart4($set, $quiz),
                5 => $this->createWritingFullTest($set, $quiz),
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
    private function createWritingPart1(Set $set, Quiz $quiz, $overridePart = null)
    {
        $metadata = [
            'instructions' => "You want to join a sports club. Fill in the form.",
            'fields' => [
                ['label' => 'Full Name', 'placeholder' => 'e.g. Nguyen Van A'],
                ['label' => 'Date of Birth', 'placeholder' => 'e.g. 01/01/1990'],
                ['label' => 'Nationality', 'placeholder' => 'e.g. Vietnamese'],
                ['label' => 'Languages Spoken', 'placeholder' => 'e.g. English, Vietnamese'],
                ['label' => 'Interests', 'placeholder' => 'e.g. Football, Reading'],
            ],
            'sample_answer' => [
                'Full Name' => 'Nguyen Van An',
                'Date of Birth' => '15/05/1995',
                'Nationality' => 'Vietnamese',
                'Languages Spoken' => 'Vietnamese, English (B2)',
                'Interests' => 'Swimming, Tennis, Reading Science Fiction'
            ]
        ];

        $part = $overridePart ?? $quiz->part;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $part,
            'type' => 'writing-part-1',
            'title' => "Writing Part 1 - Form Filling",
            'stem' => "Please fill in the form below.",
            'point' => 5,
            'order' => 1,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createWritingPart2(Set $set, Quiz $quiz, $overridePart = null)
    {
        $metadata = [
            'scenario' => "You are a new member of the sports club. Write an email to your friend telling them about the club.",
            'word_limit' => ['min' => 20, 'max' => 30],
            'hints' => "Tell them where it is, what you do there, and why you like it.",
            'sample_answer' => "Hi John! I just joined a new sports club near my house. It has a great pool and gym. I go there every weekend effectively. It's really fun!"
        ];

        $part = $overridePart ?? $quiz->part;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $part,
            'type' => 'writing-part-2',
            'title' => "Writing Part 2 - Email",
            'stem' => "Write a short email.",
            'point' => 5,
            'order' => 2,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createWritingPart3(Set $set, Quiz $quiz, $overridePart = null)
    {
        $metadata = [
            'questions' => [
                [
                    'prompt' => "A member asks: 'I've never played sports before. Is this club suitable for beginners?'",
                    'word_limit' => ['min' => 30, 'max' => 40]
                ],
                [
                    'prompt' => "Another member posts: 'The changing rooms are always dirty. Does anyone else agree?'",
                    'word_limit' => ['min' => 30, 'max' => 40]
                ],
                [
                    'prompt' => "The manager posts: 'We are planning a social event next month. Any suggestions?'",
                    'word_limit' => ['min' => 30, 'max' => 40]
                ]
            ],
            'sample_answer' => [
                "Don't worry! This club is perfect for beginners. The coaches are very friendly and there are classes specifically for new members. You will fit right in!",
                "I totally agree with you. I was there yesterday and the floor was muddy. We should complain to the manager so they hire more cleaners.",
                "How about a BBQ party in the garden? It would be a great way for members to socialize and enjoy some good food after working out."
            ]
        ];

        $part = $overridePart ?? $quiz->part;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $part,
            'type' => 'writing-part-3',
            'title' => "Writing Part 3 - Social Media Response",
            'stem' => "You are chatting in the club's social media group. Respond to the messages.",
            'point' => 10,
            'order' => 3, 
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }

    private function createWritingPart4(Set $set, Quiz $quiz, $overridePart = null)
    {
        $metadata = [
            'context' => "You are a member of a local environmental group. You received this email from the secretary of the group.",
            'email' => [
                'greeting' => "Dear Member,",
                'body' => "I am writing to inform you that we are planning to organize a 'Green Week' in our neighborhood next month. We would like to have some activities that involve everyone in the community. What activities do you think we should include, and how can we get more people to join us? Please let me know your thoughts.",
                'sign_off' => "Best regards,\nThe Secretary"
            ],
            'task1' => [
                'instruction' => "Write an email to a friend who is also a member of the group. Tell your friend about the email you received and suggest some activities. Write about 50 words. You have 10 minutes.",
                'word_limit' => ['min' => 40, 'max' => 50],
                'sample_answer' => "Hi Sam! Did you see the secretary's email about 'Green Week'? I'm thinking we could suggest a community garden project and a plastic recycling workshop. It would be a great way to involve everyone and improve our neighborhood. Let me know what you think!"
            ],
            'task2' => [
                'instruction' => "Write an email to the secretary. Explain your suggestions for 'Green Week' and how to attract more participants. Write 120-150 words. You have 20 minutes.",
                'word_limit' => ['min' => 120, 'max' => 150],
                'sample_answer' => "Dear Secretary,\n\nThank you for the email regarding the upcoming 'Green Week'. I think this is a fantastic initiative for our community.\n\nI would like to suggest two main activities. First, a community garden project where residents can plant local flowers and vegetables. This would provide a shared space for people to gather and learn about sustainability. Second, a series of workshops on plastic recycling and composting might be very beneficial.\n\nTo attract more participants, I suggest we promote the event through social media and local school newsletters. We could also offer small incentives, like 'green' certificates or eco-friendly prizes, for those who participate in multiple activities. Additionally, creating a fun, family-oriented atmosphere with music and refreshments would likely encourage more families to join.\n\nI hope these suggestions are helpful. I look forward to hearing more about the plans.\n\nBest regards,\n[Your Name]"
            ]
        ];

        $part = $overridePart ?? $quiz->part;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => $quiz->skill,
            'part' => $part,
            'type' => 'writing-part-4',
            'title' => "Writing Part 4 - Email Chain",
            'stem' => "Read the email and complete the two tasks.",
            'point' => 20, // Sum of points for Part 4 & 5
            'order' => 4,
            'metadata' => $metadata,
        ]);
        $set->questions()->attach($question->id);
    }
    
    private function createWritingFullTest(Set $set, Quiz $quiz)
    {
        $this->createWritingPart1($set, $quiz, 1);
        $this->createWritingPart2($set, $quiz, 2);
        $this->createWritingPart3($set, $quiz, 3);
        $this->createWritingPart4($set, $quiz, 4);
    }
}
