document.addEventListener('alpine:init', () => {
    Alpine.data('readingPart1', (initialData = null) => ({
        items: Array.from({ length: 5 }, () => ({
            paragraph: '',
            choices: ['', '', ''],
            correctIndex: null
        })),

        init() {
            if (initialData && initialData.paragraphs) {
                // Ensure choices and correct_answers exist to prevent errors
                const choices = initialData.choices || [];
                const correctAnswers = initialData.correct_answers || [];

                this.items = initialData.paragraphs.map((p, i) => ({
                    paragraph: p,
                    choices: choices[i] || ['', '', ''],
                    // Ensure we handle both string "0" and integer 0, and check for undefined
                    correctIndex: (() => {
                        let val = correctAnswers[i];
                        if (val === undefined || val === null) return null;
                        
                        // Handle legacy data where answers are "a", "b", "c"
                        if (typeof val === 'string' && isNaN(parseInt(val))) {
                            const map = { 'a': 0, 'b': 1, 'c': 2, 'd': 3, 'A': 0, 'B': 1, 'C': 2, 'D': 3 };
                            return map[val] !== undefined ? map[val] : null;
                        }
                        
                        return parseInt(val);
                    })()
                }));
            }
        },

        getMetadata() {
            return {
                paragraphs: this.items.map(i => i.paragraph),
                choices: this.items.map(i => i.choices),
                correct_answers: this.items.map(i => i.correctIndex)
            };
        }
    }));
});
