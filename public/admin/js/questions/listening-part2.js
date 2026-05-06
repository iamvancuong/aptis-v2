document.addEventListener('alpine:init', () => {
    Alpine.data('listeningPart2', (metadata = null) => ({
        items: metadata?.items || ['Speaker A', 'Speaker B', 'Speaker C', 'Speaker D'],
        choices: metadata?.choices || ['', '', '', '', '', ''],
        correctAnswers: metadata?.correct_answers || [0, 0, 0, 0],
        descriptions: Array.isArray(metadata?.descriptions) ? metadata.descriptions : (metadata?.descriptions ? Object.values(metadata.descriptions) : ['', '', '', '']),
        
        init() {
            // Ensure we have exactly 4 descriptions
            if (!this.descriptions || !Array.isArray(this.descriptions)) {
                this.descriptions = ['', '', '', ''];
            }
            while (this.descriptions.length < 4) {
                this.descriptions.push('');
            }
        }
    }));
});
