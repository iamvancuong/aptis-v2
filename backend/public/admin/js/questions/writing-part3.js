document.addEventListener('alpine:init', () => {
    Alpine.data('writingPart3', (initialData = null) => ({
        items: [
            { prompt: '', wordLimitMin: 30, wordLimitMax: 40 },
            { prompt: '', wordLimitMin: 30, wordLimitMax: 40 },
            { prompt: '', wordLimitMin: 30, wordLimitMax: 40 }
        ],
        sampleAnswers: ['', '', ''],

        init() {
            if (initialData && initialData.questions) {
                this.items = initialData.questions.map(q => ({
                    prompt: q.prompt || '',
                    wordLimitMin: q.word_limit?.min || 30,
                    wordLimitMax: q.word_limit?.max || 40
                }));
                // Load sample answers
                if (initialData.sample_answer && Array.isArray(initialData.sample_answer)) {
                    this.sampleAnswers = initialData.sample_answer;
                } else if (initialData.sample_answer && typeof initialData.sample_answer === 'object') {
                    // Handle object format { "Response 1": "...", "Response 2": "...", ... }
                    this.sampleAnswers = Object.values(initialData.sample_answer);
                }
                // Ensure same length as items
                while (this.sampleAnswers.length < this.items.length) {
                    this.sampleAnswers.push('');
                }
            }
        }
    }));
});
