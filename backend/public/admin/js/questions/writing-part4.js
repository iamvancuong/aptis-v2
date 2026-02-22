document.addEventListener('alpine:init', () => {
    Alpine.data('writingPart4', (initialData = null) => ({
        // Shared context - the email/letter student reads
        context: '',
        emailGreeting: '',
        emailBody: '',
        emailSignOff: '',

        // Task 1: Informal email to friend (~50 words)
        task1Instruction: '',
        task1WordLimitMin: 40,
        task1WordLimitMax: 50,
        task1SampleAnswer: '',

        // Task 2: Formal email to manager (~120-150 words)
        task2Instruction: '',
        task2WordLimitMin: 120,
        task2WordLimitMax: 150,
        task2SampleAnswer: '',

        init() {
            if (initialData) {
                this.context = initialData.context || '';
                this.emailGreeting = initialData.email?.greeting || '';
                this.emailBody = initialData.email?.body || '';
                this.emailSignOff = initialData.email?.sign_off || '';

                this.task1Instruction = initialData.task1?.instruction || '';
                this.task1WordLimitMin = initialData.task1?.word_limit?.min || 40;
                this.task1WordLimitMax = initialData.task1?.word_limit?.max || 50;
                this.task1SampleAnswer = initialData.task1?.sample_answer || '';

                this.task2Instruction = initialData.task2?.instruction || '';
                this.task2WordLimitMin = initialData.task2?.word_limit?.min || 120;
                this.task2WordLimitMax = initialData.task2?.word_limit?.max || 150;
                this.task2SampleAnswer = initialData.task2?.sample_answer || '';
            }
        }
    }));
});
