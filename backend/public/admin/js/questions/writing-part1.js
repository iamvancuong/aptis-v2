document.addEventListener('alpine:init', () => {
    Alpine.data('writingPart1', (initialData = null) => ({
        items: [
            { label: '', placeholder: '' },
            { label: '', placeholder: '' },
            { label: '', placeholder: '' },
            { label: '', placeholder: '' },
            { label: '', placeholder: '' }
        ],
        instructions: '',
        sampleAnswer: {},

        init() {
            if (initialData) {
                if (initialData.fields && Array.isArray(initialData.fields)) {
                    this.items = initialData.fields.map(f => ({
                        label: f.label || '',
                        placeholder: f.placeholder || ''
                    }));
                }
                this.instructions = initialData.instructions || '';
                if (initialData.sample_answer) {
                    this.sampleAnswer = initialData.sample_answer;
                    // Convert object to items array for editing
                    this.sampleItems = Object.entries(initialData.sample_answer).map(([key, value]) => ({
                        key, value
                    }));
                }
            }
        },

        get sampleItems() {
            // Generate sample answer items from current fields
            return this.items.filter(i => i.label).map(i => ({
                key: i.label,
                value: this.sampleAnswer[i.label] || ''
            }));
        },

        set sampleItems(val) {
            // Update sampleAnswer object from items
            const obj = {};
            val.forEach(item => {
                if (item.key) obj[item.key] = item.value;
            });
            this.sampleAnswer = obj;
        }
    }));
});
