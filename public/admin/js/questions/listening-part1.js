document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart1', (metadata = null) => ({
        choices: metadata?.choices || ['', '', ''],
        correctAnswer: metadata?.correct_answer ?? 0,
        description: metadata?.description || '',
        
        init() {
            // Data is initialized from metadata parameter or defaults
        }
    }));
});
