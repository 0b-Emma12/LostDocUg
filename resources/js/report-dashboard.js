import Alpine from 'alpinejs';

Alpine.data('reportDashboard', (reports) => ({
    reports,
    filter: 'all',
    get filteredReports() {
        return this.filter === 'all' ? this.reports : this.reports.filter((report) => report.kind === this.filter);
    },
    documentLabel(type) {
        const labels = {
            nira_id: 'National ID',
            passport: 'Passport',
            driving_licence: 'Driving permit',
            land_title: 'Land title',
            academic_certificate: 'Academic certificate',
            student_id: 'Student ID',
            bank_card: 'Bank card',
            voter_document: 'Electoral document',
        };
        return labels[type] || 'Document';
    },
    statusLabel(status) {
        return {
            processing: 'Received',
            active: 'Active',
            under_review: 'Under review',
            matched: 'Possible match',
            recovered: 'Recovered',
            closed: 'Closed',
            rejected: 'Closed',
            expired: 'Expired',
        }[status] || status;
    },
    formatDate(value) {
        return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value));
    },
}));
