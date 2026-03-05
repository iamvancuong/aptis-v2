document.addEventListener('alpine:init', () => {
    Alpine.data('readingPart3', (initialData = null) => ({
        options: ['', '', '', ''], // Standard 4 options (A, B, C, D)
        questions: Array.from({ length: 7 }, () => ({ text: '', correctIndex: null })), // Default 7 questions

        init() {
            console.log('Reading Part 3 Init Start');
            console.log('Received initialData:', initialData);

            if (initialData) {
                // Safe access to options
                if (initialData.options && Array.isArray(initialData.options)) {
                    console.log('Found options:', initialData.options);
                    this.options = [
                        initialData.options[0] ?? '',
                        initialData.options[1] ?? '',
                        initialData.options[2] ?? '',
                        initialData.options[3] ?? ''
                    ];
                } else {
                    console.warn('Options missing or not array in initialData');
                }

                // Safe access to questions
                if (initialData.questions && Array.isArray(initialData.questions)) {
                    console.log('Found questions:', initialData.questions);
                    this.questions = initialData.questions.map((q, i) => {
                        // Handle Correct Answer Index
                        let correctIdx = null;
                        if (initialData.correct_answers && initialData.correct_answers[i] !== undefined) {
                            let val = initialData.correct_answers[i];
                            // Parse string "3" to int 3
                            correctIdx = parseInt(val);
                            if (isNaN(correctIdx)) correctIdx = null;
                        }

                        return {
                            text: q,
                            correctIndex: correctIdx
                        };
                    });
                    console.log('Mapped questions:', this.questions);
                } else {
                    console.warn('Questions missing or not array in initialData');
                }
            } else {
                console.warn('No initialData provided to Reading Part 3');
            }
        },

        addQuestion() {
            this.questions.push({ text: '', correctIndex: null });
        },

        removeQuestion(index) {
            this.questions.splice(index, 1);
        },

        getChar(index) {
            return String.fromCharCode(65 + index); // 0 -> A, 1 -> B ...
        }
    }));
});
