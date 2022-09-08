import React from 'react';
import { Grid, Table, Input, Dropdown, Icon } from 'semantic-ui-react';
import Nav from './Nav';
import TopNav from './TopNav';
import tableHelper from './TableHelper';
import { getData, postData } from './DataManager';

const moment = require('moment');

const titles = {date: 'subject'};

export default class Week extends React.Component {
    constructor(props) {
        super(props);

        this.rowFilter = this.rowFilter.bind(this);
        this.displayDateColumn = this.displayDateColumn.bind(this);
        this.displayDateHeader = this.displayDateHeader.bind(this);
        this.toggleExpandedSubject = this.toggleExpandedSubject.bind(this);
        this.toggleExpandedSubjectsAll = this.toggleExpandedSubjectsAll.bind(this);

        let dt = props.match.params.dt || moment().format('YYYYMMDD');

        this.state = {
            data: [],
            date: dt,
            pid: 1,
            columns: [],
            subjects: [],
            expandedSubjects: [],
            allSubjectsExpanded: false,
            total: { date:'', minutes: 0, notes:'' }
        };
    }

    componentDidMount() {
        this.handleData();
    }

    handleData() {
        var url = '/acad/wk/' + this.state.pid + '/' + this.state.date;
        getData(url)
        .then(data => {
            if (data.data.table.rows.length > 0) {
                let total = this.state.total;
                let subjects = this.state.subjects;
                total.minutes = 0;
                data.data.table.rows.forEach ((r, k) => {
                    if (r.is_total === "1") {
                        total.minutes = total.minutes + parseInt(r.minutes);
                        subjects.push(r.hide_subject);
                    }
                });
                this.setState({
                    personId: 1,
                    data: data.data.table.rows,
                    columns: data.data.table.columns,
                    total: total,
                    subjects: subjects,
                });
            }
            console.log(this.state)
            // this.loading(false);
        });
    }

    formatDate(yyyymmdd) {
        return yyyymmdd.substring(0,4) + '-' + yyyymmdd.substring(4,6) + '-' + yyyymmdd.substring(6,8);
    }

    displayDateColumn(vals, rowIdx, colIdx) {
        let val = vals.date;
        let display = null;
        if (vals.is_total === '1') {
            let dir = this.state.expandedSubjects.indexOf(vals.hide_subject) >= 0 ? 'down' : 'right';
            display = (
                <div className="acad-bold" >
                    { val } <Icon name={`triangle ${dir}`} className="pointer" onClick={() => {this.toggleExpandedSubject(vals.hide_subject)}} />
                </div>
            );
        } else {
            let url = '/dt/' + moment(val).format('YYYYMMDD');
            display = (
                <div className="acad-indent">
                    <a href={url}>
                        { val }
                    </a>
                </div>
            )
        }
        return (
            <Table.Cell key={'dd_' + rowIdx + '_' + colIdx}>
                { display }
            </Table.Cell>
        )
    }

    displayDateHeader(vals) {
        let dir = this.state.allSubjectsExpanded ? 'down' : 'right';
        return (
            <Table.HeaderCell key={'h_' + vals.uid}>
                {titles[vals.uid]} <Icon name={`triangle ${dir}`} className="pointer" onClick={ this.toggleExpandedSubjectsAll } />
            </Table.HeaderCell>
        );
    }

    toggleExpandedSubject(subj) {
        let expandedSubjects = this.state.expandedSubjects;
        let idx = expandedSubjects.indexOf(subj);
        if (idx < 0) {
            expandedSubjects.push(subj);
        } else {
            expandedSubjects.splice(idx, 1); 
        }

        this.setState({ expandedSubjects });
    }

    toggleExpandedSubjectsAll() {
        let expandedSubjects = this.state.expandedSubjects;
        let allSubjectsExpanded = this.state.allSubjectsExpanded;
        if (!this.state.allSubjectsExpanded) {
            allSubjectsExpanded = true;
            this.state.subjects.forEach(subj => {
                expandedSubjects.push(subj);
            });
        } else {
            expandedSubjects = [];
            allSubjectsExpanded = false;
        }

        this.setState({ expandedSubjects, allSubjectsExpanded });
    }

    rowFilter(row) {
        return this.state.expandedSubjects.indexOf(row.hide_subject) >= 0 || row.is_total !== "0";
    }

    render() {
        let dt = this.formatDate(this.state.date);
        var propsForTable = {
            data: this.state.data,
            columns: this.state.columns,
            specialCols: {
                date: this.displayDateColumn,
            },
            rowFilter: this.rowFilter,
            total: this.state.total,
            titles: {
                date: this.displayDateHeader,
            },
        }
        var tbl = tableHelper.tablify(propsForTable);
        let topNavProps = JSON.parse(JSON.stringify(this.props));
        topNavProps.title = 'Week starting ' + moment(dt).format('MMM D, YYYY');
        topNavProps.prevLink = '/wk/' + moment(dt).subtract(7, 'days').format('YYYYMMDD');
        topNavProps.nextLink = '/wk/' + moment(dt).add(7, 'days').format('YYYYMMDD');
        return (
            <div>
                <Grid>
                    <Grid.Row>
                        <Grid.Column width={3}>
                            <Nav date={dt}/>
                        </Grid.Column>
                        <Grid.Column width={13}>
                            <Grid.Row>
                                <Grid.Column centered="true">
                                    <TopNav { ...topNavProps } />
                                </Grid.Column>
                            </Grid.Row>
                            {tbl}
                        </Grid.Column>
                    </Grid.Row>
                </Grid>
            </div>
        );
    }
}
