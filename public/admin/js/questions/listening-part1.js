document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart1', (metadata = null) => ({
        title: metadata?.title || (typeof question !== 'undefined' ? question.title : ''),
        stem: metadata?.stem || (typeof question !== 'undefined' ? question.stem : ''),
        choices: metadata?.choices || ['', '', ''],
        correctAnswer: metadata?.correct_answer ?? 0,
        description: metadata?.description || '',
        
        init() {
            // Data is initialized from metadata parameter or defaults
        }
    }));
});
