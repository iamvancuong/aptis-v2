document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart4', (metadata = null) => ({
        topic: metadata?.topic || '',
        questions: metadata?.questions || [
            { question: '', choices: ['', '', ''] },
            { question: '', choices: ['', '', ''] }
        ],
        correctAnswers: metadata?.correct_answers || [0, 0],
        
        init() {
            // Data is initialized from metadata parameter or defaults
        }
    }));
});
