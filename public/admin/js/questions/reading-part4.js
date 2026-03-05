document.addEventListener('alpine:init', () => {
    Alpine.data('readingPart4', (initialData = null) => ({
        paragraphs: ['', '', '', '', '', '', ''],
        headings: ['', '', '', '', '', '', ''],
        matches: Array(7).fill(null),

        init() {
            if (initialData) {
                this.$nextTick(() => {
                    if (initialData.headings && Array.isArray(initialData.headings)) {
                        this.headings = initialData.headings;
                    }

                    if (initialData.paragraphs && Array.isArray(initialData.paragraphs)) {
                        this.paragraphs = initialData.paragraphs;
                    }
                    
                    if (initialData.correct_answers && Array.isArray(initialData.correct_answers)) {
                        this.matches = initialData.correct_answers.map(val => {
                            let parsed = parseInt(val);
                            return isNaN(parsed) ? null : parsed; 
                        });
                        
                        if (this.matches.length !== this.paragraphs.length) {
                             let newMatches = Array(this.paragraphs.length).fill(null);
                             this.matches.forEach((m, i) => { if(i < newMatches.length) newMatches[i] = m; });
                             this.matches = newMatches;
                        }
                    }
                });
            }
        },

        addParagraph() {
            this.paragraphs.push('');
            this.matches.push(null);
        },

        removeParagraph(index) {
            this.paragraphs.splice(index, 1);
            this.matches.splice(index, 1);
        },

        addHeading() {
            this.headings.push('');
        },

        removeHeading(index) {
            this.headings.splice(index, 1);
            this.matches = this.matches.map(m => (m >= this.headings.length) ? null : m);
        },

        getHeadingLabel(index) {
            return (index + 1).toString();
        }
    }));
});
