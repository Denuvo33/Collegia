<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    AlertTriangle,
    Building2,
    Calendar,
    CheckCircle,
    Clock,
    FileText,
    MessageSquare,
    Settings2,
    ShieldCheck,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

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
        pending: number;
        approved: number;
        completed: number;
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

const props = defineProps<{
    stats: Stats;
    recentUsers: User[];
    recentEvents: Event[];
}>();

// Calculate percentages for visual representation
const eventStatusPercentages = computed(() => {
    const total = props.stats.events.total || 1;
    return {
        pending: Math.round((props.stats.events.pending / total) * 100),
        approved: Math.round((props.stats.events.approved / total) * 100),
        completed: Math.round((props.stats.events.completed / total) * 100),
    };
});

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
                            <div class="stat-breakdown-item status-pending">
                                <Clock :size="14" />
                                <span>{{ stats.events.pending }} Pending</span>
                            </div>
                            <div class="stat-breakdown-item status-approved">
                                <CheckCircle :size="14" />
                                <span>{{ stats.events.approved }} Approved</span>
                            </div>
                            <div class="stat-breakdown-item status-completed">
                                <XCircle :size="14" />
                                <span>{{ stats.events.completed }} Completed</span>
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
            <div class="charts-grid">
                <!-- Event Status Distribution -->
                <Card class="chart-card">
                    <CardHeader>
                        <CardTitle class="chart-title">Event Status Distribution</CardTitle>
                        <CardDescription>Overview of event statuses</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="chart-bars">
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <Clock :size="16" />
                                    <span>Pending</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-pending" :style="{ width: eventStatusPercentages.pending + '%' }">
                                        <span class="chart-bar-value">{{ stats.events.pending }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <CheckCircle :size="16" />
                                    <span>Approved</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-approved" :style="{ width: eventStatusPercentages.approved + '%' }">
                                        <span class="chart-bar-value">{{ stats.events.approved }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-bar-label">
                                    <XCircle :size="16" />
                                    <span>Completed</span>
                                </div>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar chart-bar-completed" :style="{ width: eventStatusPercentages.completed + '%' }">
                                        <span class="chart-bar-value">{{ stats.events.completed }}</span>
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
    color: #a3a3a3;
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

/* Charts Grid */
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

.chart-bar-report {
    background: linear-gradient(90deg, #171717, #404040);
}

.chart-bar-value {
    min-width: 2rem;
    text-align: right;
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
