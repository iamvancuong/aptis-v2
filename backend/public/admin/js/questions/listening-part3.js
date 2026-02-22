document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart3', (metadata = null) => ({
        topic: metadata?.topic || '',
        sharedChoices: metadata?.shared_choices || ['Man', 'Woman', 'Both'],
        statements: metadata?.statements || ['', '', '', ''],
        correctAnswers: metadata?.correct_answers || [0, 0, 0, 0],
        
        init() {
            // Data is initialized from metadata parameter or defaults
        }
    }));
});
