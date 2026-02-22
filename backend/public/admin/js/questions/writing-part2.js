document.addEventListener('alpine:init', () => {
    Alpine.data('writingPart2', (initialData = null) => ({
        scenario: '',
        wordLimitMin: 20,
        wordLimitMax: 30,
        hints: '',
        sampleAnswer: '',

        init() {
            if (initialData) {
                this.scenario = initialData.scenario || '';
                this.wordLimitMin = initialData.word_limit?.min || 20;
                this.wordLimitMax = initialData.word_limit?.max || 30;
                this.hints = initialData.hints || '';
                this.sampleAnswer = initialData.sample_answer || '';
            }
        }
    }));
});
