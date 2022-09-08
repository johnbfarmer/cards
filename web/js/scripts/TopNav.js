import React from 'react';
import { Icon } from 'semantic-ui-react';
const moment = require('moment');

const TopNav = (props) => {
    let isDisabledPrev = props.prevLink === null;
    let isDisabledNext = props.nextLink === null;
    let title = props.title || moment().format('MMM D, YYYY');

    return (
        <div className='title-area'>
            <h3>
                <a href={ props.prevLink }>
                    <Icon link disabled={ isDisabledPrev } name='angle left' />
                </a>
                { title }
                <a href={ props.nextLink }>
                    <Icon link disabled={ isDisabledNext } name='angle right' />
                </a>
            </h3>
        </div>
    );
}

module.exports = TopNav;
