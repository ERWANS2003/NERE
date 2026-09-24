import React from "react";
import { Row, Col, Progress, Table } from "reactstrap";

import Widget from "../../components/Widget";
import { MiningStatCard, MiningCard, MiningBadge, MiningAlert } from "../../components/NereComponents";

import Calendar from "./components/calendar/Calendar";
import Map from "./components/am4chartMap/am4chartMap";
import Rickshaw from "./components/rickshaw/Rickshaw";

import AnimateNumber from "react-animated-number";

import s from "./Dashboard.module.scss";

import peopleA1 from "../../assets/people/a1.jpg";
import peopleA2 from "../../assets/people/a2.jpg";
import peopleA5 from "../../assets/people/a5.jpg";
import peopleA4 from "../../assets/people/a4.jpg";

/**
 * DashboardNereMining - Enhanced dashboard with Néré Mining branding
 * 
 * Features:
 * - Néré Mining color scheme (gold, crimson, charcoal)
 * - Mining-themed stat cards
 * - Animated counters and transitions
 * - Dark theme with gold accents
 * - Professional ITSM dashboard layout
 */
class DashboardNereMining extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      graph: null,
      checkedArr: [false, false, false],
      stats: {
        openTickets: 24,
        resolvedToday: 8,
        pendingTickets: 12,
        avgResponse: 120,
      },
      recentTickets: [
        {
          id: "TKT-001",
          title: "Network connectivity issue",
          status: "in-progress",
          priority: "high",
          assignee: "John Doe",
          lastUpdate: "2 hours ago",
        },
        {
          id: "TKT-002",
          title: "Database backup configuration",
          status: "open",
          priority: "medium",
          assignee: "Jane Smith",
          lastUpdate: "30 minutes ago",
        },
        {
          id: "TKT-003",
          title: "Security update deployment",
          status: "resolved",
          priority: "critical",
          assignee: "Mike Johnson",
          lastUpdate: "1 hour ago",
        },
      ],
    };
    this.checkTable = this.checkTable.bind(this);
  }

  checkTable(id) {
    let arr = [];
    if (id === 0) {
      const val = !this.state.checkedArr[0];
      for (let i = 0; i < this.state.checkedArr.length; i += 1) {
        arr[i] = val;
      }
    } else {
      arr = this.state.checkedArr;
      arr[id] = !arr[id];
    }
    if (arr[0]) {
      let count = 1;
      for (let i = 1; i < arr.length; i += 1) {
        if (arr[i]) {
          count += 1;
        }
      }
      if (count !== arr.length) {
        arr[0] = !arr[0];
      }
    }
    this.setState({
      checkedArr: arr,
    });
  }

  getStatusBadge(status) {
    const statusMap = {
      open: { type: 'info', label: 'Open', icon: 'la la-circle' },
      'in-progress': { type: 'gold', label: 'In Progress', icon: 'la la-hourglass-half' },
      resolved: { type: 'success', label: 'Resolved', icon: 'la la-check-circle' },
      closed: { type: 'default', label: 'Closed', icon: 'la la-times-circle' },
      critical: { type: 'danger', label: 'Critical', icon: 'la la-exclamation-triangle' },
    };

    const statusConfig = statusMap[status] || statusMap.open;
    return (
      <MiningBadge type={statusConfig.type} icon={statusConfig.icon}>
        {statusConfig.label}
      </MiningBadge>
    );
  }

  getPriorityBadge(priority) {
    const priorityMap = {
      low: { type: 'info', label: 'Low' },
      medium: { type: 'gold', label: 'Medium' },
      high: { type: 'danger', label: 'High' },
      critical: { type: 'danger', label: 'Critical' },
    };

    const priorityConfig = priorityMap[priority] || priorityMap.medium;
    return (
      <MiningBadge type={priorityConfig.type}>
        {priorityConfig.label}
      </MiningBadge>
    );
  }

  render() {
    const { stats, recentTickets } = this.state;

    return (
      <div className={s.root}>
        <div className="fade-in-up">
          <h1 className="page-title text-gold mb-4">
            ITSM Dashboard &nbsp;
            <small>
              <small>Néré Mining Operations</small>
            </small>
          </h1>

          {/* Alert Section */}
          <Row className="mb-4">
            <Col lg={12}>
              <MiningAlert type="info" title="System Status" dismissible>
                All systems operational. Last update: 5 minutes ago.
              </MiningAlert>
            </Col>
          </Row>

          {/* KPI Stats Row */}
          <Row className="mb-4 slide-in-left">
            <Col lg={3} md={6} sm={12} className="mb-3">
              <MiningStatCard
                title="Open Tickets"
                value={stats.openTickets}
                color="gold"
                trend={5}
                icon="la la-ticket"
              />
            </Col>
            <Col lg={3} md={6} sm={12} className="mb-3">
              <MiningStatCard
                title="Resolved Today"
                value={stats.resolvedToday}
                color="green"
                trend={12}
                icon="la la-check-circle"
              />
            </Col>
            <Col lg={3} md={6} sm={12} className="mb-3">
              <MiningStatCard
                title="Pending Items"
                value={stats.pendingTickets}
                color="crimson"
                trend={-8}
                icon="la la-hourglass-half"
              />
            </Col>
            <Col lg={3} md={6} sm={12} className="mb-3">
              <MiningStatCard
                title="Avg Response (min)"
                value={stats.avgResponse}
                color="blue"
                trend={3}
                icon="la la-tachometer"
              />
            </Col>
          </Row>

          {/* Main Content Grid */}
          <Row className="mb-4">
            {/* Map Section - 7 columns */}
            <Col lg={7}>
              <Widget className="bg-transparent">
                <Map />
              </Widget>
            </Col>
            <Col lg={1} />

            {/* Map Statistics - 4 columns */}
            <Col lg={4}>
              <MiningCard accent="gold" title="Map Statistics" subtitle="Real-time tracking">
                <p className="mb-3">
                  Status: <strong className="text-gold">Live</strong>
                </p>
                <p className="mb-3">
                  <span className="circle bg-gold text-white mr-2">
                    <i className="fa fa-map-marker" />
                  </span>
                  146 Countries, 2759 Cities
                </p>

                {/* Foreign Visits */}
                <div className="row progress-stats mb-3">
                  <div className="col-md-9 col-12">
                    <h6 className="name fw-semi-bold text-gold">Foreign Visits</h6>
                    <p className="description deemphasize mb-xs text-white">
                      Global reach
                    </p>
                    <Progress
                      color="gold"
                      value="60"
                      className="bg-subtle-blue progress-xs"
                    />
                  </div>
                  <div className="col-md-3 col-12 text-center">
                    <span className="status rounded rounded-lg bg-gold text-dark">
                      <small>
                        <AnimateNumber value={75} />%
                      </small>
                    </span>
                  </div>
                </div>

                {/* Local Visits */}
                <div className="row progress-stats mb-3">
                  <div className="col-md-9 col-12">
                    <h6 className="name fw-semi-bold text-gold">Local Visits</h6>
                    <p className="description deemphasize mb-xs text-white">
                      Regional traffic
                    </p>
                    <Progress
                      color="danger"
                      value="39"
                      className="bg-subtle-blue progress-xs"
                    />
                  </div>
                  <div className="col-md-3 col-12 text-center">
                    <span className="status rounded rounded-lg bg-danger text-white">
                      <small>
                        <AnimateNumber value={84} />%
                      </small>
                    </span>
                  </div>
                </div>

                {/* Sound Frequencies */}
                <div className="row progress-stats">
                  <div className="col-md-9 col-12">
                    <h6 className="name fw-semi-bold text-gold">System Load</h6>
                    <p className="description deemphasize mb-xs text-white">
                      Average usage
                    </p>
                    <Progress
                      color="success"
                      value="80"
                      className="bg-subtle-blue progress-xs"
                    />
                  </div>
                  <div className="col-md-3 col-12 text-center">
                    <span className="status rounded rounded-lg bg-success text-white">
                      <small>
                        <AnimateNumber value={92} />%
                      </small>
                    </span>
                  </div>
                </div>
              </MiningCard>
            </Col>
          </Row>

          {/* Recent Tickets */}
          <Row className="mb-4">
            <Col lg={12}>
              <MiningCard accent="crimson" title="Recent Tickets" subtitle="Latest support requests">
                <div className="table-responsive">
                  <Table hover className="table-mining mb-0">
                    <thead>
                      <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assignee</th>
                        <th>Last Update</th>
                      </tr>
                    </thead>
                    <tbody>
                      {recentTickets.map((ticket, idx) => (
                        <tr key={idx}>
                          <td className="text-gold font-weight-bold">{ticket.id}</td>
                          <td>{ticket.title}</td>
                          <td>{this.getStatusBadge(ticket.status)}</td>
                          <td>{this.getPriorityBadge(ticket.priority)}</td>
                          <td>{ticket.assignee}</td>
                          <td className="text-muted">{ticket.lastUpdate}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              </MiningCard>
            </Col>
          </Row>

          {/* Charts Row */}
          <Row>
            <Col lg={6}>
              <Widget
                className="bg-transparent"
                title={<h5>Ticket <span className="fw-semi-bold">Trends</span></h5>}
                settings
                refresh
              >
                <Rickshaw />
              </Widget>
            </Col>
            <Col lg={6}>
              <Widget
                className="bg-transparent"
                title={<h5>Calendar <span className="fw-semi-bold">Schedule</span></h5>}
                settings
                refresh
              >
                <Calendar />
              </Widget>
            </Col>
          </Row>
        </div>
      </div>
    );
  }
}

export default DashboardNereMining;
