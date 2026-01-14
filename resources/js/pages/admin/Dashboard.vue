<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Ban,
    Building2,
    Calendar,
    CheckCircle,
    Clock,
    DollarSign,
    Eye,
    EyeOff,
    FileText,
    MessageSquare,
    Settings2,
    ShieldCheck,
    TimerOff,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Stats = {
    users: {
        total: number;
        admin: number;
        moderator: number;
        community: number;
        user: number;
    };
    posts: number;
    comments: number;
    events: {
        total: number;
        visible: number;
        notVisible: number;
    };
    transactions: {
        total: number;
        success: number;
        pending: number;
        failed: number;
        expired: number;
        cancelled: number;
    };
    reports: {
        total: number;
        posts: number;
        comments: number;
        events: number;
    };
};

type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    role: string;
};

type Event = {
    id: number;
    title: string;
    status: string;
    created_at: string;
    user: User;
};

type MonthlyTransaction = {
    month: number;
    name: string;
    amount: number;
    count: number;
};

type Transaction = {
    id: number;
    order_id: string;
    user_name: string;
    event_title: string;
    amount: number;
    status: string;
    created_at: string;
};

const props = defineProps<{
    stats: Stats;
    monthlyTransactions: MonthlyTransaction[];
    recentUsers: User[];
    recentEvents: Event[];
    recentTransactions: Transaction[];
    selectedYear: number;
    availableYears: number[];
}>();

// Tooltip state
const tooltipData = ref<{
    visible: boolean;
    x: number;
    y: number;
    amount: number;
    count: number;
    month: string;
}>({
    visible: false,
    x: 0,
    y: 0,
    amount: 0,
    count: 0,
    month: '',
});

// Year change handler
const changeYear = (year: string) => {
    router.visit(route('admin.dashboard', { year: parseInt(year) }), {
        preserveState: true,
        preserveScroll: true,
    });
};

// Calculate percentages for visual representation
const eventVisibilityPercentages = computed(() => {
    const total = props.stats.events.total || 1;
    return {
        visible: Math.round((props.stats.events.visible / total) * 100),
        notVisible: Math.round((props.stats.events.notVisible / total) * 100),
    };
});

const transactionStatusPercentages = computed(() => {
    const total = props.stats.transactions.total || 1;
    return {
        success: Math.round((props.stats.transactions.success / total) * 100),
        pending: Math.round((props.stats.transactions.pending / total) * 100),
        failed: Math.round((props.stats.transactions.failed / total) * 100),
        expired: Math.round((props.stats.transactions.expired / total) * 100),
        cancelled: Math.round((props.stats.transactions.cancelled / total) * 100),
    };
});

const maxTransactionAmount = computed(() => {
    return Math.max(...props.monthlyTransactions.map((t) => t.amount), 1);
});

// Generate SVG path for line chart
const generateLinePath = () => {
    if (!props.monthlyTransactions || props.monthlyTransactions.length === 0) return '';

    const points = props.monthlyTransactions.map((month, index) => {
        const x = index * 50 + 25;
        const y = 300 - (month.amount / maxTransactionAmount.value) * 280;
        return `${x},${y}`;
    });

    return `M ${points.join(' L ')}`;
};

// Generate SVG path for area under the line
const generateAreaPath = () => {
    if (!props.monthlyTransactions || props.monthlyTransactions.length === 0) return '';

    const points = props.monthlyTransactions.map((month, index) => {
        const x = index * 50 + 25;
        const y = 300 - (month.amount / maxTransactionAmount.value) * 280;
        return `${x},${y}`;
    });

    const firstX = 25;
    const lastX = (props.monthlyTransactions.length - 1) * 50 + 25;

    return `M ${firstX},300 L ${points.join(' L ')} L ${lastX},300 Z`;
};

const reportBreakdown = computed(() => {
    const total = props.stats.reports.total || 1;
    return {
        posts: Math.round((props.stats.reports.posts / total) * 100),
        comments: Math.round((props.stats.reports.comments / total) * 100),
        events: Math.round((props.stats.reports.events / total) * 100),
    };
});

// Format date
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Get status badge variant based on date-calculated status
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'Upcoming':
            return 'default';
        case 'Today':
            return 'secondary';
        case 'Complete':
            return 'outline';
        default:
            return 'secondary';
    }
};

// Get transaction status variant
const getTransactionStatusVariant = (status: string) => {
    switch (status) {
        case 'success':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
        case 'expired':
        case 'cancelled':
            return 'outline';
        default:
            return 'outline';
    }
};

// Show tooltip on hover
const showTooltip = (month: MonthlyTransaction, event: MouseEvent) => {
    const svg = (event.currentTarget as SVGElement).ownerSVGElement;
    if (!svg) return;

    const rect = svg.getBoundingClientRect();
    tooltipData.value = {
        visible: true,
        x: event.clientX - rect.left,
        y: event.clientY - rect.top - 10,
        amount: month.amount,
        count: month.count,
        month: month.name,
    };
};

// Hide tooltip
const hideTooltip = () => {
    tooltipData.value.visible = false;
};
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="dashboard-container">
            <!-- Header -->
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Dashboard Overview</h1>
                    <p class="dashboard-subtitle">Monitor your platform's key metrics and activities</p>
                </div>
                <div class="dashboard-time">
                    {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <!-- Users Card -->
                <Card class="stat-card stat-card-hover">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-users">
                            <Users :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Total Users</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value">{{ stats.users.total.toLocaleString() }}</div>
                        <div class="stat-breakdown">
                            <div class="stat-breakdown-item">
                                <Users :size="14" />
                                <span>{{ stats.users.user }} User</span>
                            </div>
                            <div class="stat-breakdown-item">
                                <Building2 :size="14" />
                                <span>{{ stats.users.community }} Community</span>
                            </div>

                            <div class="stat-breakdown-item">
                                <Settings2 :size="14" />
                                <span>{{ stats.users.moderator }} Moderator</span>
                            </div>
                            <div class="stat-breakdown-item">
                                <ShieldCheck :size="14" />
                                <span>{{ stats.users.admin }} Admin</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Posts Card -->
                <Card class="stat-card stat-card-hover">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-posts">
                            <FileText :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Total Posts</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value">{{ stats.posts.toLocaleString() }}</div>
                        <div class="stat-description">
                            <TrendingUp :size="14" />
                            <span>User-generated content</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Events Card -->
                <Card class="stat-card stat-card-hover">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-events">
                            <Calendar :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Total Events</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value">{{ stats.events.total.toLocaleString() }}</div>
                        <div class="stat-breakdown">
                            <div class="stat-breakdown-item status-visible">
                                <Eye :size="14" />
                                <span>{{ stats.events.visible }} Visible</span>
                            </div>
                            <div class="stat-breakdown-item status-not-visible">
                                <EyeOff :size="14" />
                                <span>{{ stats.events.notVisible }} Not Visible</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Transactions Card -->
                <Card class="stat-card stat-card-hover">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-transactions">
                            <DollarSign :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Total Transactions</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value">{{ stats.transactions.total.toLocaleString() }}</div>
                        <div class="stat-breakdown">
                            <div class="stat-breakdown-item transaction-success">
                                <CheckCircle :size="14" />
                                <span>{{ stats.transactions.success }} Success</span>
                            </div>
                            <div class="stat-breakdown-item transaction-pending">
                                <Clock :size="14" />
                                <span>{{ stats.transactions.pending }} Pending</span>
                            </div>
                            <div class="stat-breakdown-item transaction-failed">
                                <XCircle :size="14" />
                                <span>{{ stats.transactions.failed }} Failed</span>
                            </div>
                            <div class="stat-breakdown-item transaction-expired">
                                <TimerOff :size="14" />
                                <span>{{ stats.transactions.expired }} Expired</span>
                            </div>
                            <div class="stat-breakdown-item transaction-cancelled">
                                <Ban :size="14" />
                                <span>{{ stats.transactions.cancelled }} Cancelled</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Comments Card -->
                <Card class="stat-card stat-card-hover">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-comments">
                            <MessageSquare :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Total Comments</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value">{{ stats.comments.toLocaleString() }}</div>
                        <div class="stat-description">
                            <TrendingUp :size="14" />
                            <span>Community engagement</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reports Card -->
                <Card class="stat-card stat-card-hover stat-card-reports">
                    <CardHeader class="stat-card-header">
                        <div class="stat-icon-wrapper stat-icon-reports">
                            <AlertTriangle :size="24" :stroke-width="2" />
                        </div>
                        <CardTitle class="stat-card-title">Pending Reports</CardTitle>
                    </CardHeader>
                    <CardContent class="stat-card-content">
                        <div class="stat-value stat-value-alert">{{ stats.reports.total.toLocaleString() }}</div>
                        <div class="stat-breakdown">
                            <div class="stat-breakdown-item">
                                <FileText :size="14" />
                                <span>{{ stats.reports.posts }} Posts</span>
                            </div>
                            <div class="stat-breakdown-item">
                                <MessageSquare :size="14" />
                                <span>{{ stats.reports.comments }} Comments</span>
                            </div>
                            <div class="stat-breakdown-item">
                                <Calendar :size="14" />
                                <span>{{ stats.reports.events }} Events</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <!-- Transaction Trends - Full Width -->
            <Card class="chart-card transaction-trends-card">
                <CardHeader>
                    <div class="chart-header-row">
                        <div>
                            <CardTitle class="chart-title">Transaction Trends</CardTitle>
                            <CardDescription>Monthly successful transaction amounts & counts</CardDescription>
                        </div>
                        <Select :model-value="selectedYear.toString()" @update:model-value="changeYear">
                            <SelectTrigger class="year-selector">
                                <SelectValue placeholder="Select Year" />
                            </SelectTrigger>
                            <SelectContent align="end" side="bottom">
                                <SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
                                    {{ year }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="line-chart">
                        <div class="chart-container">
                            <!-- Y-axis labels -->
                            <div class="y-axis-labels">
                                <div v-for="i in 6" :key="'y-' + i" class="y-label">
                                    Rp {{ Math.round((maxTransactionAmount / 5) * (6 - i)).toLocaleString() }}
                                </div>
                            </div>

                            <div class="svg-wrapper">
                                <svg viewBox="0 0 600 300" class="line-chart-svg">
                                    <!-- Grid lines -->
                                    <g class="grid-lines">
                                        <line v-for="i in 5" :key="'h-' + i" x1="0" :y1="i * 60" x2="600" :y2="i * 60" />
                                    </g>

                                    <!-- Line path -->
                                    <path :d="generateLinePath()" class="line-path" fill="none" stroke="url(#lineGradient)" stroke-width="3" />

                                    <!-- Area fill -->
                                    <path :d="generateAreaPath()" class="area-path" fill="url(#areaGradient)" />

                                    <!-- Data points -->
                                    <g class="data-points">
                                        <circle
                                            v-for="(month, index) in monthlyTransactions"
                                            :key="month.month"
                                            :cx="index * 50 + 25"
                                            :cy="300 - (month.amount / maxTransactionAmount) * 280"
                                            r="6"
                                            class="data-point"
                                            @mouseenter="showTooltip(month, $event)"
                                            @mouseleave="hideTooltip"
                                        />
                                    </g>

                                    <!-- Gradients -->
                                    <defs>
                                        <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color: #166534; stop-opacity: 1" />
                                            <stop offset="100%" style="stop-color: #15803d; stop-opacity: 1" />
                                        </linearGradient>
                                        <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" style="stop-color: #166534; stop-opacity: 0.3" />
                                            <stop offset="100%" style="stop-color: #166534; stop-opacity: 0" />
                                        </linearGradient>
                                    </defs>
                                </svg>

                                <!-- Tooltip -->
                                <div
                                    v-if="tooltipData.visible"
                                    class="chart-tooltip"
                                    :style="{ left: tooltipData.x + 'px', top: tooltipData.y + 'px' }"
                                >
                                    <div class="tooltip-month">{{ tooltipData.month }} {{ selectedYear }}</div>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Amount:</span>
                                        <span class="tooltip-value">Rp {{ Math.round(tooltipData.amount).toLocaleString() }}</span>
                                    </div>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Count:</span>
                                        <span class="tooltip-value">{{ tooltipData.count }} transactions</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- X-axis labels -->
                        <div class="chart-axis-labels">
                            <div v-for="month in monthlyTransactions" :key="month.month" class="axis-label">
                                {{ month.name }}
                            </div>
                        </div>

                        <!-- Stats Summary -->
                        <div class="chart-stats-summary">
                            <div class="stat-item">
                                <span class="stat-label">Total Amount ({{ selectedYear }}):</span>
                                <span class="stat-value"
                                    >Rp
                                    {{
                                        Math.round(monthlyTransactions.reduce((sum, m) => sum + parseFloat(m.amount || 0), 0)).toLocaleString()
                                    }}</span
                                >
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Transactions:</span>
                                <span class="stat-value">{{ monthlyTransactions.reduce((sum, m) => sum + parseInt(m.count || 0), 0) }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Event Visibility & Report Breakdown - Row Below -->
            <div class="charts-grid">
                <!-- Event Visibility Distribution -->
                <Card class="chart-card">
                    <CardHeader>
                        <CardTitle class="chart-title">Event Visibility</CardTitle>
                        <CardDescription>Overview of visible and hidden events</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="chart-bars">
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <Eye :size="16" />
                                    <span>Visible</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-visible" :style="{ width: eventVisibilityPercentages.visible + '%' }">
                                        <span class="chart-bar-value">{{ stats.events.visible }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <EyeOff :size="16" />
                                    <span>Not Visible</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-not-visible" :style="{ width: eventVisibilityPercentages.notVisible + '%' }">
                                        <span class="chart-bar-value">{{ stats.events.notVisible }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Report Breakdown -->
                <Card class="chart-card">
                    <CardHeader>
                        <CardTitle class="chart-title">Report Breakdown</CardTitle>
                        <CardDescription>Distribution of reported content</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="chart-bars">
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <FileText :size="16" />
                                    <span>Posts</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-report" :style="{ width: reportBreakdown.posts + '%' }">
                                        <span class="chart-bar-value">{{ stats.reports.posts }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <MessageSquare :size="16" />
                                    <span>Comments</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-report" :style="{ width: reportBreakdown.comments + '%' }">
                                        <span class="chart-bar-value">{{ stats.reports.comments }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <Calendar :size="16" />
                                    <span>Events</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-report" :style="{ width: reportBreakdown.events + '%' }">
                                        <span class="chart-bar-value">{{ stats.reports.events }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity Section -->
            <div class="activity-grid">
                <!-- Recent Users -->
                <Card class="activity-card">
                    <CardHeader>
                        <CardTitle class="activity-title">Recent Users</CardTitle>
                        <CardDescription>Latest registered users</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="activity-list">
                            <div v-for="user in recentUsers" :key="user.id" class="activity-item">
                                <div class="activity-item-icon">
                                    <Users :size="16" />
                                </div>
                                <div class="activity-item-content">
                                    <div class="activity-item-title">{{ user.name }}</div>
                                    <div class="activity-item-subtitle">{{ user.email }}</div>
                                </div>
                                <div class="activity-item-meta">
                                    <Badge :variant="user.role === 'community' ? 'default' : 'outline'" class="role-badge">
                                        {{ user.role }}
                                    </Badge>
                                    <span class="activity-item-date">{{ formatDate(user.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Events -->
                <Card class="activity-card">
                    <CardHeader>
                        <CardTitle class="activity-title">Recent Events</CardTitle>
                        <CardDescription>Latest created events</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="activity-list">
                            <div v-for="event in recentEvents" :key="event.id" class="activity-item">
                                <div class="activity-item-icon">
                                    <Calendar :size="16" />
                                </div>
                                <div class="activity-item-content">
                                    <div class="activity-item-title">{{ event.title }}</div>
                                    <div class="activity-item-subtitle">by {{ event.user.name }}</div>
                                </div>
                                <div class="activity-item-meta">
                                    <Badge :variant="getStatusVariant(event.status)" class="status-badge">
                                        {{ event.status }}
                                    </Badge>
                                    <span class="activity-item-date">{{ formatDate(event.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <Card class="activity-card">
                    <CardHeader>
                        <CardTitle class="activity-title">Recent Transactions</CardTitle>
                        <CardDescription>Latest payment transactions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="activity-list">
                            <div v-for="transaction in recentTransactions" :key="transaction.id" class="activity-item">
                                <div class="activity-item-icon">
                                    <DollarSign :size="16" />
                                </div>
                                <div class="activity-item-content">
                                    <div class="activity-item-title">{{ transaction.order_id }}</div>
                                    <div class="activity-item-subtitle">{{ transaction.user_name }} • {{ transaction.event_title }}</div>
                                </div>
                                <div class="activity-item-meta">
                                    <Badge :variant="getTransactionStatusVariant(transaction.status)" class="status-badge">
                                        {{ transaction.status }}
                                    </Badge>
                                    <span class="activity-item-amount">Rp {{ Math.round(transaction.amount).toLocaleString() }}</span>
                                    <span class="activity-item-date">{{ formatDate(transaction.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Dashboard Container */
.dashboard-container {
    padding: 2rem;
    background: linear-gradient(to bottom, #fafafa, #ffffff);
    min-height: 100vh;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e5e5;
}

.dashboard-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #000000, #404040);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    color: #737373;
    font-size: 1rem;
    font-weight: 400;
}

.dashboard-time {
    color: #525252;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    background: #f5f5f5;
    border-radius: 0.5rem;
    border: 1px solid #e5e5e5;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    position: relative;
    border: 1px solid #e5e5e5;
    background: #ffffff;
    transition: all 0.3s ease;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #000000, #404040);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.stat-card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.15);
    border-color: #d4d4d4;
}

.stat-card-hover:hover::before {
    transform: scaleX(1);
}

.stat-card-reports {
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
}

.stat-card-header {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 1rem;
    padding-bottom: 0.5rem;
}

.stat-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    background: #f5f5f5;
    border: 1px solid #e5e5e5;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon-wrapper {
    background: #000000;
    border-color: #000000;
    color: #ffffff;
}

.stat-card-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #525252;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-card-content {
    padding-top: 0;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #000000;
    line-height: 1;
    margin-bottom: 1rem;
    letter-spacing: -0.03em;
}

.stat-value-alert {
    background: linear-gradient(135deg, #000000, #404040);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-breakdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #737373;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.25rem 0;
}

.stat-breakdown-item svg {
    color: #525252;
}

.stat-description {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #737373;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.status-pending {
    color: #525252;
}

.status-approved {
    color: #171717;
}

.status-completed {
    color: #737373;
}

.status-visible {
    color: #171717;
}

.status-not-visible {
    color: #737373;
}

.transaction-success {
    color: #166534;
}

.transaction-pending {
    color: #92400e;
}

.transaction-failed {
    color: #991b1b;
}

.transaction-expired {
    color: #854d0e;
}

.transaction-cancelled {
    color: #6b7280;
}

/* Charts Grid */
.transaction-trends-card {
    border: 1px solid #e5e5e5;
    background: #ffffff;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    width: 100%;
}

.transaction-trends-card:hover {
    box-shadow: 0 8px 16px -8px rgba(0, 0, 0, 0.1);
    border-color: #d4d4d4;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card {
    border: 1px solid #e5e5e5;
    background: #ffffff;
    transition: all 0.3s ease;
}

.chart-card:hover {
    box-shadow: 0 8px 16px -8px rgba(0, 0, 0, 0.1);
    border-color: #d4d4d4;
}

.chart-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #171717;
}

.chart-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.year-selector {
    min-width: 120px;
}

.chart-container {
    display: flex;
    gap: 1rem;
    position: relative;
}

.y-axis-labels {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 100px;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.y-label {
    font-size: 0.75rem;
    color: #737373;
    font-weight: 500;
    text-align: right;
    white-space: nowrap;
}

.svg-wrapper {
    flex: 1;
    position: relative;
}

.chart-bars {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.chart-bar-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chart-bar-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #525252;
}

.chart-bar-wrapper {
    position: relative;
    width: 100%;
    height: 2.5rem;
    background: #f5f5f5;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e5e5e5;
}

.chart-bar {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 1rem;
    font-weight: 700;
    font-size: 0.875rem;
    color: #ffffff;
    transition: width 0.6s ease;
    border-radius: 0.5rem;
}

.chart-bar-pending {
    background: linear-gradient(90deg, #404040, #525252);
}

.chart-bar-approved {
    background: linear-gradient(90deg, #000000, #262626);
}

.chart-bar-completed {
    background: linear-gradient(90deg, #525252, #737373);
}

.chart-bar-visible {
    background: linear-gradient(90deg, #000000, #262626);
}

.chart-bar-not-visible {
    background: linear-gradient(90deg, #525252, #737373);
}

.chart-bar-transaction {
    background: linear-gradient(90deg, #166534, #15803d);
}

.chart-bar-report {
    background: linear-gradient(90deg, #171717, #404040);
}

.chart-bar-value {
    min-width: 2rem;
    text-align: right;
}

/* Line Chart Styles */
.line-chart {
    width: 100%;
    position: relative;
}

.line-chart-svg {
    width: 100%;
    height: 300px;
}

.chart-tooltip {
    position: absolute;
    background: #171717;
    color: #ffffff;
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    pointer-events: none;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translate(-50%, -100%);
    min-width: 160px;
}

.tooltip-month {
    font-weight: 700;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #404040;
    font-size: 0.875rem;
}

.tooltip-row {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
}

.tooltip-label {
    color: #a3a3a3;
    margin-right: 0.5rem;
}

.tooltip-value {
    font-weight: 600;
    color: #ffffff;
}

.chart-stats-summary {
    display: flex;
    justify-content: space-around;
    padding: 1rem;
    background: #fafafa;
    border-radius: 0.5rem;
    border: 1px solid #e5e5e5;
    margin-top: 1rem;
}

.chart-stats-summary .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.chart-stats-summary .stat-label {
    font-size: 0.75rem;
    color: #737373;
    font-weight: 500;
}

.chart-stats-summary .stat-value {
    font-size: 1rem;
    color: #171717;
    font-weight: 700;
}

.grid-lines line {
    stroke: #e5e5e5;
    stroke-width: 1;
}

.line-path {
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 2px 4px rgba(22, 101, 52, 0.2));
}

.area-path {
    opacity: 0.6;
}

.data-point {
    fill: #ffffff;
    stroke: #166534;
    stroke-width: 3;
    cursor: pointer;
    transition: all 0.3s ease;
}

.data-point:hover {
    r: 7;
    stroke-width: 4;
    filter: drop-shadow(0 2px 8px rgba(22, 101, 52, 0.4));
}

.chart-axis-labels {
    display: flex;
    justify-content: space-between;
    padding: 0 1rem;
    margin-bottom: 1rem;
}

.axis-label {
    font-size: 0.75rem;
    color: #737373;
    font-weight: 600;
    text-align: center;
    flex: 1;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e5e5;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #525252;
    font-weight: 500;
}

.legend-color {
    width: 1rem;
    height: 0.25rem;
    background: linear-gradient(90deg, #166534, #15803d);
    border-radius: 0.125rem;
}

/* Activity Grid */
.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.activity-card {
    border: 1px solid #e5e5e5;
    background: #ffffff;
    transition: all 0.3s ease;
}

.activity-card:hover {
    box-shadow: 0 8px 16px -8px rgba(0, 0, 0, 0.1);
    border-color: #d4d4d4;
}

.activity-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #171717;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #f5f5f5;
    border-radius: 0.5rem;
    background: #fafafa;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: #f5f5f5;
    border-color: #e5e5e5;
    transform: translateX(4px);
}

.activity-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    background: #ffffff;
    border: 1px solid #e5e5e5;
    color: #525252;
    flex-shrink: 0;
}

.activity-item-content {
    flex: 1;
    min-width: 0;
}

.activity-item-title {
    font-weight: 600;
    color: #171717;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.activity-item-subtitle {
    font-size: 0.75rem;
    color: #737373;
    margin-top: 0.125rem;
}

.activity-item-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    flex-shrink: 0;
}

.activity-item-date {
    font-size: 0.75rem;
    color: #a3a3a3;
    font-weight: 500;
}

.activity-item-amount {
    font-size: 0.875rem;
    color: #166534;
    font-weight: 700;
}

.role-badge,
.status-badge {
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card,
.chart-card,
.activity-card {
    animation: fadeInUp 0.6s ease forwards;
}

.stat-card:nth-child(1) {
    animation-delay: 0.05s;
}
.stat-card:nth-child(2) {
    animation-delay: 0.1s;
}
.stat-card:nth-child(3) {
    animation-delay: 0.15s;
}
.stat-card:nth-child(4) {
    animation-delay: 0.2s;
}
.stat-card:nth-child(5) {
    animation-delay: 0.25s;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }

    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
    }

    .dashboard-title {
        font-size: 1.875rem;
    }

    .stats-grid,
    .charts-grid,
    .activity-grid {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 2rem;
    }
}
</style>
