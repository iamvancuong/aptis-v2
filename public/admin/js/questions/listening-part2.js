document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart2', (metadata = null) => ({
        items: metadata?.items || ['Speaker A', 'Speaker B', 'Speaker C', 'Speaker D'],
        choices: metadata?.choices || ['', '', '', '', '', ''],
        correctAnswers: metadata?.correct_answers || [0, 0, 0, 0],
        description: metadata?.description || '',
        
        init() {
            // Data is initialized from metadata parameter or defaults
            if (!this.descriptions || this.descriptions.length < 4) {
                this.descriptions = ['', '', '', ''];
            }
        }
    }));
});
